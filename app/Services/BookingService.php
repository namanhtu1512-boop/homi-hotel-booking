<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingItemRoom;
use App\Models\BookingStatusLog;
use App\Models\HotelInfo;
use App\Models\Payment;
use App\Models\Room;
use App\Models\PaymentStatusLog;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingStatusChanged;
use App\Notifications\NewBookingReceived;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Trẻ em (6-11 tuổi, cột `children`) tối đa mỗi phòng — trẻ sơ sinh
     * (0-5 tuổi, cột `infants`) miễn phí và không tính vào giới hạn này lẫn
     * sức chứa phòng; từ 12 tuổi trở lên khai vào ô người lớn.
     */
    private const MAX_CHILDREN_PER_ROOM = 2;

    public function __construct(
        private AvailabilityService $availabilityService,
        private PricingService $pricingService,
        private PromotionService $promotionService,
        private RoomHoldService $roomHoldService,
        private VNPayService $vnPayService,
    ) {}

    // ----------------------------------------------------------------
    // CUSTOMER
    // ----------------------------------------------------------------

    /**
     * Admin/staff tạo booking thủ công (không cần user account — dùng cho đoàn/nhóm
     * liên hệ qua form group-booking hoặc điện thoại).
     */
    public function createByAdmin(array $data): Booking
    {
        $this->availabilityService->validateDates($data['check_in'], $data['check_out']);

        $roomTypes = collect($data['items'])
            ->mapWithKeys(fn (array $item) => [
                (int) $item['room_type_id'] => RoomType::where('status', 'active')->findOrFail($item['room_type_id']),
            ]);

        // Cùng rule sức chứa như create() của khách — admin/staff tạo đơn hộ
        // không được phép bỏ qua giới hạn số khách/phòng.
        $this->validateGuestCapacity($data['items'], $roomTypes);
        $this->checkAvailabilityForAllItems($data['items'], $roomTypes, $data['check_in'], $data['check_out']);

        return DB::transaction(function () use ($data, $roomTypes) {
            RoomType::whereIn('id', $roomTypes->keys()->sort()->values())->lockForUpdate()->get();

            $nights = null;
            $total  = 0;
            $lines  = [];

            foreach ($data['items'] as $item) {
                $roomType = $roomTypes[(int) $item['room_type_id']];
                $quantity = (int) $item['quantity'];

                if (! $this->availabilityService->canBook($roomType->id, $data['check_in'], $data['check_out'], $quantity)) {
                    throw ValidationException::withMessages([
                        'items' => ["Phòng \"{$roomType->name}\" đã hết trong khoảng thời gian này."],
                    ]);
                }

                $pricing = $this->pricingService->calculate($roomType, $data['check_in'], $data['check_out'], $quantity, (int) ($item['children'] ?? 0));
                $nights ??= $pricing['nights'];
                $total  += $pricing['total_price'];

                $lines[] = [
                    'room_type_id'    => $roomType->id,
                    'quantity'        => $quantity,
                    'adults'          => (int) ($item['adults'] ?? 1),
                    'children'        => (int) ($item['children'] ?? 0),
                    'infants'         => (int) ($item['infants'] ?? 0),
                    'price_per_night' => $pricing['unit_price'],
                    'nights'          => $pricing['nights'],
                    'subtotal'        => $pricing['room_subtotal'],
                    'child_surcharge' => $pricing['child_surcharge'],
                    'price_breakdown' => $pricing['nightly_breakdown'],
                ];
            }

            $booking = Booking::create([
                'user_id'        => $data['user_id'] ?? null,
                'booking_code'   => $this->generateCode(),
                'check_in'       => $data['check_in'],
                'check_out'      => $data['check_out'],
                'nights'         => $nights,
                'adults'         => array_sum(array_column($data['items'], 'adults')),
                'children'       => array_sum(array_column($data['items'], 'children')),
                'infants'        => array_sum(array_map(fn ($item) => (int) ($item['infants'] ?? 0), $data['items'])),
                'customer_name'  => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'note'           => $data['note'] ?? null,
                'total_amount'   => $total,
                'discount_amount'=> 0,
                'status'         => BookingStatus::CONFIRMED,
            ]);

            $this->logStatus($booking, null, BookingStatus::CONFIRMED, Auth::id(), 'Admin/staff tạo đơn thủ công — tự động xác nhận.');

            // Chỉ báo được nếu đơn có gắn tài khoản khách hàng (đơn nhóm/điện
            // thoại đôi khi không có, xem $data['user_id'] ?? null ở trên).
            $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã được xác nhận, bạn có thể thanh toán ngay."));

            foreach ($lines as $line) {
                $booking->bookingItems()->create($line);
            }

            $booking->payment()->create([
                'amount' => $total,
                'status' => PaymentStatus::UNPAID,
                'method' => PaymentMethod::PAY_AT_HOTEL,
            ]);

            return $booking->load(['bookingItems.roomType', 'payment']);
        });
    }

    public function create(User $customer, array $data): Booking
    {
        // Session đang giữ room hold cho chính khoảng ngày/phòng này — hold
        // của chính nó không được tính là "đã bị chiếm" khi re-check trong
        // transaction (xem RoomHoldService, AvailabilityService).
        $holdSessionId = $data['_hold_session_id'] ?? null;

        // DateRangeService validate đã được gọi qua AvailabilityService
        $this->availabilityService->validateDates($data['check_in'], $data['check_out']);

        // Nạp trước các loại phòng active theo id trong items để tính sức chứa
        // và giá — findOrFail giữ hành vi 404 khi có id không hợp lệ/không active.
        $roomTypes = collect($data['items'])
            ->mapWithKeys(fn (array $item) => [
                (int) $item['room_type_id'] => RoomType::where('status', 'active')
                    ->findOrFail($item['room_type_id']),
            ]);

        // Kiểm tra sức chứa theo TỪNG loại phòng — mỗi dòng có capacity riêng
        // (roomType.capacity × quantity của chính dòng đó), không gộp chung
        // với các dòng khác trong đơn.
        $this->validateGuestCapacity($data['items'], $roomTypes);

        // Kiểm tra trước (gom TẤT CẢ dòng hết phòng vào 1 thông báo) — xem
        // checkAvailabilityForAllItems(). Bên trong DB::transaction() vẫn
        // còn 1 lượt re-check có lockForUpdate() làm lưới an toàn cuối cùng
        // chống race condition, chỉ báo dòng đầu tiên nếu trúng race hiếm gặp.
        $this->checkAvailabilityForAllItems($data['items'], $roomTypes, $data['check_in'], $data['check_out'], $holdSessionId);

        return DB::transaction(function () use ($customer, $data, $roomTypes, $holdSessionId) {
            // Khóa các loại phòng liên quan theo thứ tự id tăng dần (tránh
            // deadlock khi 2 đơn cùng khóa nhiều loại phòng chung) TRƯỚC khi
            // tính lại availability. SELECT ... FOR UPDATE luôn đọc dữ liệu
            // mới nhất đã commit, nên nếu 2 khách đặt cùng lúc, người đến sau
            // phải chờ người đến trước commit xong rồi mới thấy đúng số phòng
            // còn lại — chống overbooking khi 2 request chạy song song.
            RoomType::whereIn('id', $roomTypes->keys()->sort()->values())
                ->lockForUpdate()
                ->get();

            $nights         = null;
            $total          = 0;
            $totalAdults    = 0;
            $totalChildren  = 0;
            $totalInfants   = 0;
            $lines          = [];

            foreach ($data['items'] as $item) {
                $roomType = $roomTypes[(int) $item['room_type_id']];
                $quantity = (int) $item['quantity'];
                $adults   = (int) $item['adults'];
                $children = (int) ($item['children'] ?? 0);
                $infants  = (int) ($item['infants'] ?? 0);

                if (! $this->availabilityService->canBook(
                    $roomType->id,
                    $data['check_in'],
                    $data['check_out'],
                    $quantity,
                    $holdSessionId
                )) {
                    throw ValidationException::withMessages([
                        'items' => ["Phòng \"{$roomType->name}\" đã hết trong khoảng thời gian này."],
                    ]);
                }

                $pricing = $this->pricingService->calculate(
                    $roomType,
                    $data['check_in'],
                    $data['check_out'],
                    $quantity,
                    $children
                );

                $nights        ??= $pricing['nights'];
                $total          += $pricing['total_price'];
                $totalAdults    += $adults;
                $totalChildren  += $children;
                $totalInfants   += $infants;

                $lines[] = [
                    'room_type_id'    => $roomType->id,
                    'quantity'        => $quantity,
                    'adults'          => $adults,
                    'children'        => $children,
                    'infants'         => $infants,
                    'price_per_night' => $pricing['unit_price'],
                    'nights'          => $pricing['nights'],
                    'subtotal'        => $pricing['room_subtotal'],
                    'child_surcharge' => $pricing['child_surcharge'],
                    'price_breakdown' => $pricing['nightly_breakdown'],
                ];
            }

            $promotions = collect();
            $discount   = 0;
            $promoLines = [];

            if (! empty($data['promo_codes'])) {
                $promotions = $this->promotionService->findValidManyByCodes($data['promo_codes']);

                // Mỗi mã tính giảm trên PHẦN CÒN LẠI sau các mã trước đó (tuần
                // tự), tự động cap về 0 — không thể giảm vượt quá tổng đơn dù
                // stack bao nhiêu mã cũng vậy.
                $remaining = $total;
                foreach ($promotions as $promotion) {
                    $lineDiscount = min((int) $promotion->discountFor($remaining), $remaining);
                    $discount    += $lineDiscount;
                    $remaining   -= $lineDiscount;
                    $promoLines[] = ['promotion_id' => $promotion->id, 'discount_amount' => $lineDiscount];
                }
            }

            $booking = Booking::create([
                'user_id'         => $customer->id,
                'promotion_id'    => $promotions->first()?->id,
                'booking_code'    => $this->generateCode(),
                'check_in'        => $data['check_in'],
                'check_out'       => $data['check_out'],
                'nights'          => $nights,
                'adults'          => $totalAdults,
                'children'        => $totalChildren,
                'infants'         => $totalInfants,
                'customer_name'   => $data['customer_name'],
                'customer_phone'  => $data['customer_phone'],
                'customer_email'  => $data['customer_email'] ?? $customer->email,
                'note'            => $data['note'] ?? null,
                'total_amount'    => $total - $discount,
                'discount_amount' => $discount,
                'status'          => BookingStatus::CONFIRMED,
            ]);

            $this->logStatus($booking, null, BookingStatus::CONFIRMED, $customer->id, 'Khách tạo đơn đặt phòng — tự động xác nhận, không cần chờ admin/staff duyệt.');

            // Thông báo cho admin/staff về đơn mới
            User::whereIn('role', ['admin', 'staff'])->each(
                fn (User $u) => $u->notify(new NewBookingReceived($booking))
            );

            // Đơn tự động CONFIRMED ngay khi tạo (không còn bước staff duyệt
            // riêng) — trước đây khách chỉ biết đơn đã xác nhận khi admin/staff
            // bấm "Xác nhận đơn" (gửi qua confirm()); giờ phải báo ngay tại đây,
            // nếu không khách không nhận được thông báo nào về việc đơn đã
            // sẵn sàng thanh toán.
            $customer->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã được xác nhận, bạn có thể thanh toán ngay."));

            foreach ($lines as $line) {
                $booking->bookingItems()->create($line);
            }

            foreach ($promoLines as $promoLine) {
                $booking->promotions()->attach($promoLine['promotion_id'], ['discount_amount' => $promoLine['discount_amount']]);
            }

            $payment = $booking->payment()->create([
                'amount' => $total - $discount,
                'status' => PaymentStatus::UNPAID,
                'method' => PaymentMethod::PAY_AT_HOTEL,
            ]);
            $this->logPaymentStatus($payment, null, PaymentStatus::UNPAID, $customer->id, 'Tạo đơn đặt phòng.');

            // Đơn đã tạo thành công trong transaction này — giải phóng hold
            // của session (nếu có) ngay trong transaction, để nếu transaction
            // rollback (lỗi phát sinh sau đó) thì hold vẫn còn nguyên.
            if ($holdSessionId) {
                $this->roomHoldService->releaseForSession($holdSessionId);
            }

            return $booking->load(['bookingItems.roomType', 'serviceItems.service', 'payment']);
        });
    }

    public function myBookings(User $customer, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Booking::where('user_id', $customer->id)
            ->with(['bookingItems.roomType.images', 'payment'])
            ->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function findForCustomer(int $bookingId, User $customer): Booking
    {
        $booking = Booking::with(['bookingItems.roomType.images', 'bookingItems.rooms', 'serviceItems.service', 'payment.statusLogs.changedBy', 'promotions', 'roomChangeRequests'])
            ->findOrFail($bookingId);

        Gate::forUser($customer)->authorize('view', $booking);

        return $booking;
    }

    /**
     * @return array{booking: Booking, refund_ok: bool} xem cancelByAdmin()
     *         — `refund_ok` = false nghĩa là cần xử lý hoàn tiền thủ công.
     */
    public function cancelByCustomer(int $bookingId, User $customer): array
    {
        $booking = $this->findForCustomer($bookingId, $customer);

        if (! $booking->canCancelByCustomer()) {
            $hoursBefore = HotelInfo::instance()->cancellation_hours_before;

            throw ValidationException::withMessages([
                'status' => ["Không thể hủy đơn ở trạng thái hiện tại hoặc đã quá hạn hủy (chỉ hủy được trước {$hoursBefore} giờ so với giờ nhận phòng)."],
            ]);
        }

        $oldStatus = $booking->status;
        $booking->update(['status' => BookingStatus::CANCELLED]);
        $this->logStatus($booking, $oldStatus, BookingStatus::CANCELLED, $customer->id, 'Khách hủy đơn.');

        $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã được hủy."));

        $refundOk = $this->attemptRefund($booking, $customer->id);

        return ['booking' => $booking->fresh(['payment']), 'refund_ok' => $refundOk];
    }

    /**
     * Khách chọn thanh toán online qua cổng VNPay (sandbox) — sinh mã giao
     * dịch mới, chuyển payment sang PENDING và trả về URL để redirect khách
     * sang trang thanh toán của VNPay. Kết quả thật được xác nhận ở
     * confirmVnpayReturn() khi VNPay gọi về (return URL/IPN).
     *
     * Chỉ tính tiền theo phần CÒN THIẾU (amount - amount_collected), không
     * phải toàn bộ `amount` — nếu không, khách đã thanh toán đủ mà bị phát
     * sinh thêm phí (mở lại PENDING) sẽ bị tính lại từ đầu, trả 2 lần cho
     * phần đã thanh toán trước đó.
     *
     * @return array{booking: Booking, payment_url: string}
     */
    public function initiateVnpayPayment(int $bookingId, User $customer, string $ipAddress): array
    {
        $booking = $this->findForCustomer($bookingId, $customer);

        if (! $booking->canMarkPaymentAsPaid()) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể thanh toán khi đơn đã được xác nhận và chưa thanh toán.'],
            ]);
        }

        // canMarkPaymentAsPaid() cho phép DEPOSIT_PAID → PAID (để staff thu
        // nốt tiền mặt còn lại lúc check-in), nhưng deposit_amount (30% đã
        // thu) KHÔNG được cộng vào amount_collected — nếu cho đi tiếp qua
        // VNPay ở đây, outstanding sẽ tính nhầm bằng cả `amount` gốc (chưa
        // trừ phần đã cọc), khiến khách bị thu online thêm 100% dù đã cọc
        // 30% bằng tiền mặt. Đường cọc tiền mặt cam kết rõ với khách "phần
        // còn lại trả tiền mặt khi nhận phòng" (xem blade khách hàng) nên
        // chặn hẳn VNPay ở đây thay vì cố tính lại số tiền còn thiếu.
        if ($booking->payment->status === PaymentStatus::DEPOSIT_PAID) {
            throw ValidationException::withMessages([
                'status' => ['Đơn đã đặt cọc — phần còn lại vui lòng thanh toán bằng tiền mặt khi nhận phòng, không thanh toán qua VNPay.'],
            ]);
        }

        // Khóa dòng payment trong lúc quyết định tái sử dụng hay tạo mới
        // txnRef — nếu khách bấm nút "Thanh toán online" 2 lần liên tiếp
        // (double-click, hoặc submit lại do mạng chậm), 2 request có thể
        // chạy gần như đồng thời; không khóa thì cả hai đều đọc thấy trạng
        // thái CŨ trước khi bên kia commit, cùng quyết định KHÔNG tái sử
        // dụng, rồi cùng tạo txnRef MỚI khác nhau đè lên nhau — bên thua
        // orphan y hệt lỗi "mồ côi giao dịch" mà việc tái sử dụng txnRef bên
        // dưới vốn được thêm vào để chặn.
        [$txnRef, $outstanding] = DB::transaction(function () use ($booking, $customer) {
            $payment = Payment::whereKey($booking->payment->id)->lockForUpdate()->first();

            $outstanding = round((float) $payment->amount - (float) $payment->amount_collected, 2);

            if ($outstanding <= 0) {
                throw ValidationException::withMessages([
                    'status' => ['Đơn này không còn khoản nào cần thanh toán qua VNPay.'],
                ]);
            }

            $oldStatus = $payment->status;

            // Nếu khách bấm "Thanh toán online" lại (mở lại tab, tải lại trang,
            // link cũ...) trong khi lần trước vẫn còn PENDING với đúng số tiền
            // chưa xử lý xong, tái sử dụng transaction_code cũ thay vì tạo mới —
            // nếu tạo mới mà khách hoàn tất thanh toán ở phiên VNPay CŨ (vẫn còn
            // hiệu lực), callback trả về txnRef cũ sẽ không khớp transaction_code
            // mới trong DB nữa, khiến VNPay đã thu tiền nhưng hệ thống không ghi
            // nhận được (mồ côi giao dịch).
            $reuseExisting = $oldStatus === PaymentStatus::PENDING
                && $payment->method === PaymentMethod::ONLINE_VNPAY
                && $payment->transaction_code
                && round((float) $payment->pending_gateway_amount, 2) === $outstanding;

            $txnRef = $reuseExisting
                ? $payment->transaction_code
                : $this->vnPayService->generateTxnRef($booking->booking_code);

            if (! $reuseExisting) {
                $payment->update([
                    'method'                  => PaymentMethod::ONLINE_VNPAY,
                    'status'                  => PaymentStatus::PENDING,
                    'transaction_code'        => $txnRef,
                    'pending_gateway_amount'  => $outstanding,
                ]);

                if ($oldStatus !== PaymentStatus::PENDING) {
                    $this->logPaymentStatus($payment, $oldStatus, PaymentStatus::PENDING, $customer->id, 'Khách chuyển sang cổng VNPay để thanh toán.');
                }
            }

            return [$txnRef, $outstanding];
        });

        $paymentUrl = $this->vnPayService->buildPaymentUrl(
            $txnRef,
            $outstanding,
            'Thanh toan booking ' . $booking->booking_code,
            $ipAddress,
        );

        return ['booking' => $booking, 'payment_url' => $paymentUrl];
    }

    /**
     * Xử lý phản hồi từ VNPay (dùng chung cho cả return URL và IPN — hai nơi
     * gọi cùng logic idempotent này, chỉ khác định dạng response trả về cho
     * người gọi). Xác thực chữ ký trước khi tin bất kỳ trường nào trong
     * $query, tránh giả mạo kết quả thanh toán.
     *
     * @return array{booking: ?Booking, success: bool, message: string, code: string}
     */
    public function confirmVnpayReturn(array $query): array
    {
        $txnRef = $query['vnp_TxnRef'] ?? null;

        if (! $txnRef) {
            return ['booking' => null, 'success' => false, 'code' => 'not_found', 'message' => 'Thiếu thông tin giao dịch.'];
        }

        $payment = Payment::where('transaction_code', $txnRef)->first();

        if (! $payment) {
            return ['booking' => null, 'success' => false, 'code' => 'not_found', 'message' => 'Không tìm thấy giao dịch tương ứng.'];
        }

        $booking = $payment->booking;

        if (! $this->vnPayService->verifySecureHash($query)) {
            return ['booking' => $booking, 'success' => false, 'code' => 'invalid_signature', 'message' => 'Chữ ký không hợp lệ.'];
        }

        // VNPay có thể gọi IPN (server-to-server) gần như đồng thời với lúc
        // khách được redirect về return URL, hoặc tự động thử lại IPN nếu
        // lần gọi trước timeout — cả hai nơi đều gọi hàm này cho CÙNG 1 giao
        // dịch. Khóa dòng payment trong transaction + đọc lại status SAU khi
        // đã khóa, tránh 2 lời gọi đồng thời cùng đọc thấy PENDING trước khi
        // bên kia commit, dẫn tới cộng amount_collected/gửi thông báo 2 lần
        // cho 1 giao dịch thật.
        return DB::transaction(function () use ($payment, $booking, $query) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();

            // Giao dịch đã được xử lý trước đó (khách bấm lại nút back/refresh
            // trên trang return, hoặc IPN đã chạy trước) — không xử lý lại để
            // tránh ghi log/thông báo trùng lặp.
            if ($payment->status !== PaymentStatus::PENDING) {
                return [
                    'booking' => $booking,
                    'success' => $payment->status === PaymentStatus::PAID,
                    'code'    => 'already_confirmed',
                    'message' => $payment->status === PaymentStatus::PAID
                        ? 'Đơn đã được thanh toán trước đó.'
                        : 'Giao dịch đã được xử lý trước đó.',
                ];
            }

            $oldStatus = $payment->status;

            // Số tiền THẬT SỰ đã yêu cầu VNPay thu ở lần redirect này — so khớp
            // với vnp_Amount VNPay trả về, tránh tin nhầm 1 callback báo đúng
            // mã giao dịch nhưng sai số tiền (VD do payment.amount bị đổi giữa
            // lúc redirect và lúc VNPay gọi về, hoặc callback cũ/giả bị phát lại).
            $expectedAmount = (int) round((float) ($payment->pending_gateway_amount ?? $payment->amount) * 100);
            $callbackAmount = (int) ($query['vnp_Amount'] ?? -1);

            if ($this->vnPayService->isSuccessResponse($query) && $expectedAmount !== $callbackAmount) {
                $this->logPaymentStatus($payment, $oldStatus, $oldStatus, $booking->user_id, "VNPay báo thành công nhưng số tiền không khớp (mong đợi {$expectedAmount}, nhận {$callbackAmount}) — từ chối xác nhận, cần kiểm tra thủ công.");

                return ['booking' => $booking, 'success' => false, 'code' => 'amount_mismatch', 'message' => 'Số tiền xác nhận từ VNPay không khớp, vui lòng liên hệ khách sạn.'];
            }

            if ($this->vnPayService->isSuccessResponse($query)) {
                // vnp_PayDate đến từ VNPay theo định dạng YmdHis (vd "20260717141518")
                // — cột gateway_paid_at cast 'datetime' nên phải parse đúng format
                // trước khi gán, nếu không Carbon sẽ đoán sai định dạng chuỗi số này.
                $gatewayPaidAt = isset($query['vnp_PayDate'])
                    ? \Carbon\Carbon::createFromFormat('YmdHis', $query['vnp_PayDate'])
                    : now();

                $thisTxnAmount = (float) ($payment->pending_gateway_amount ?? $payment->amount);

                $payment->update([
                    'status'                  => PaymentStatus::PAID,
                    'paid_at'                 => now(),
                    'amount_collected'        => (float) $payment->amount_collected + $thisTxnAmount,
                    // Số tiền THẬT SỰ gắn với transaction_code/gateway_transaction_no
                    // đang được lưu (không phải tổng cộng dồn) — nếu payment này còn
                    // trải qua 1 chu kỳ VNPay khác sau đó, amount_collected sẽ tăng
                    // tiếp nhưng last_gateway_amount phải phản ánh ĐÚNG giao dịch mới
                    // nhất, để attemptRefund() không yêu cầu hoàn nhiều hơn số giao
                    // dịch hiện tại thực thu.
                    'last_gateway_amount'     => $thisTxnAmount,
                    'pending_gateway_amount'  => null,
                    'gateway_transaction_no'  => $query['vnp_TransactionNo'] ?? null,
                    'gateway_paid_at'         => $gatewayPaidAt,
                ]);
                $this->logPaymentStatus($payment, $oldStatus, PaymentStatus::PAID, $booking->user_id, 'VNPay xác nhận thanh toán thành công.');

                $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã thanh toán thành công qua VNPay."));

                return ['booking' => $booking->fresh('payment'), 'success' => true, 'code' => 'ok', 'message' => 'Thanh toán VNPay thành công.'];
            }

            $responseCode = $query['vnp_ResponseCode'] ?? 'unknown';

            $payment->update([
                'status'                 => PaymentStatus::UNPAID,
                'pending_gateway_amount' => null,
                'note'                   => 'VNPay báo lỗi/hủy giao dịch, mã phản hồi: ' . $responseCode,
            ]);
            $this->logPaymentStatus($payment, $oldStatus, PaymentStatus::UNPAID, $booking->user_id, "Thanh toán VNPay thất bại/bị hủy (mã {$responseCode}).");

            return ['booking' => $booking->fresh('payment'), 'success' => false, 'code' => 'ok', 'message' => 'Thanh toán VNPay không thành công.'];
        });
    }

    /**
     * Khách tự báo đã chuyển khoản — chuyển thanh toán sang "đang xử lý" chờ
     * admin/staff đối soát và xác nhận thủ công (không tự động sang paid).
     */
    public function markBankTransferPending(int $bookingId, User $customer): Booking
    {
        $booking = $this->findForCustomer($bookingId, $customer);

        $canReportTransfer = $booking->status === BookingStatus::CONFIRMED
            && $booking->payment
            && $booking->payment->status->canTransitionTo(PaymentStatus::PENDING);

        if (! $canReportTransfer) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể báo chuyển khoản khi đơn đã được xác nhận và chưa thanh toán.'],
            ]);
        }

        $oldStatus = $booking->payment->status;
        $booking->payment->update([
            'method' => PaymentMethod::BANK_TRANSFER,
            'status' => PaymentStatus::PENDING,
        ]);
        $this->logPaymentStatus($booking->payment, $oldStatus, PaymentStatus::PENDING, $customer->id, 'Khách báo đã chuyển khoản, chờ xác nhận.');

        return $booking->fresh('payment');
    }

    /**
     * Khách đặt cọc 30% qua kênh online (mô phỏng) — phần còn lại
     * (Booking::remainingAfterDeposit()) trả bằng tiền mặt khi nhận phòng.
     * Chỉ hợp lệ từ trạng thái UNPAID (xem Booking::canPayDeposit()); tiền
     * cọc không tự động hoàn khi hủy đơn (PaymentStatus::canRefund()).
     */
    public function payDepositDemo(int $bookingId, User $customer): Booking
    {
        $booking = $this->findForCustomer($bookingId, $customer);

        if (! $booking->canPayDeposit()) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể đặt cọc khi đơn đã được xác nhận và chưa thanh toán.'],
            ]);
        }

        $oldStatus = $booking->payment->status;
        $booking->payment->update([
            'method'                   => PaymentMethod::CASH_WITH_DEPOSIT,
            'status'                   => PaymentStatus::DEPOSIT_PAID,
            'deposit_amount'           => $booking->depositAmount(),
            'deposit_transaction_code' => 'DEPOSIT-' . Str::upper(Str::random(10)),
            'deposit_paid_at'          => now(),
        ]);
        $this->logPaymentStatus($booking->payment, $oldStatus, PaymentStatus::DEPOSIT_PAID, $customer->id, 'Khách đặt cọc 30% (mô phỏng), phần còn lại trả tiền mặt khi nhận phòng.');

        return $booking->fresh('payment');
    }

    // ----------------------------------------------------------------
    // ADMIN / STAFF
    // ----------------------------------------------------------------

    public function adminList(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Booking::with(['user', 'bookingItems.roomType', 'payment'])
            ->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_status'])) {
            $query->whereHas('payment', fn ($q) => $q->where('status', $filters['payment_status']));
        }

        if (! empty($filters['customer_id'])) {
            $query->where('user_id', $filters['customer_id']);
        }

        if (! empty($filters['booking_code'])) {
            $query->where('booking_code', $filters['booking_code']);
        }

        if (! empty($filters['customer_name'])) {
            $query->where('customer_name', 'like', '%' . $filters['customer_name'] . '%');
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        if (! empty($filters['check_in_from'])) {
            $query->whereDate('check_in', '>=', $filters['check_in_from']);
        }

        if (! empty($filters['check_in_to'])) {
            $query->whereDate('check_in', '<=', $filters['check_in_to']);
        }

        if (! empty($filters['room_type_id'])) {
            $query->whereHas('bookingItems', function ($q) use ($filters) {
                $q->where('room_type_id', $filters['room_type_id']);
            });
        }

        return $query->paginate($perPage);
    }

    public function findForAdmin(int $bookingId): Booking
    {
        return Booking::with(['user', 'bookingItems.roomType', 'bookingItems.rooms', 'serviceItems.service', 'payment.statusLogs.changedBy', 'statusLogs.changedBy'])
            ->findOrFail($bookingId);
    }

    public function adminPaymentsList(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Payment::with('booking')
            ->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['booking_code'])) {
            $query->whereHas('booking', function ($q) use ($filters) {
                $q->where('booking_code', $filters['booking_code']);
            });
        }

        if (! empty($filters['customer_name'])) {
            $query->whereHas('booking', function ($q) use ($filters) {
                $q->where('customer_name', 'like', '%' . $filters['customer_name'] . '%');
            });
        }

        return $query->paginate($perPage);
    }

    public function findPaymentForAdmin(int $paymentId): Payment
    {
        return Payment::with(['booking', 'statusLogs.changedBy'])->findOrFail($paymentId);
    }

    public function updatePaymentStatus(Booking $booking, string $status): Booking
    {
        if (! $booking->payment) {
            throw ValidationException::withMessages([
                'status' => ['Đơn này chưa có thông tin thanh toán.'],
            ]);
        }

        $oldStatus = $booking->payment->status;
        $newStatus = PaymentStatus::from($status);

        if (! $oldStatus->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => ["Không thể chuyển thanh toán từ \"{$oldStatus->label()}\" sang \"{$newStatus->label()}\"."],
            ]);
        }

        if ($newStatus === PaymentStatus::PAID && ! in_array($booking->status, [BookingStatus::CONFIRMED, BookingStatus::CHECKED_IN], true)) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể đánh dấu đã thanh toán khi đơn ở trạng thái đã xác nhận hoặc đã check-in.'],
            ]);
        }

        // Hoàn tiền thủ công chỉ hợp lệ cho đơn ĐÃ HỦY — tránh trường hợp đơn
        // đã hoàn thành (đã ở, đã trả phòng) vẫn bị đánh dấu hoàn tiền nhầm
        // (canTransitionTo() ở enum chỉ biết PAID->REFUNDED, không biết gì
        // về trạng thái đơn).
        if ($newStatus === PaymentStatus::REFUNDED && $booking->status !== BookingStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể hoàn tiền cho đơn đã hủy.'],
            ]);
        }

        $booking->payment->update([
            'status'  => $newStatus,
            'paid_at' => $newStatus === PaymentStatus::PAID ? now() : $booking->payment->paid_at,
            // Ghi nhận amount_collected cho CẢ các phương thức thủ công (tiền
            // mặt, chuyển khoản...), không chỉ VNPay — nếu không, một khoản
            // phụ phí phát sinh sau đó (applyExtraCharge()) mở lại PAID→PENDING
            // sẽ khiến attemptRefund() không còn cách nào biết khoản tiền mặt/
            // chuyển khoản này đã thực sự được thu, và bỏ qua hoàn tiền khi hủy.
            'amount_collected' => $newStatus === PaymentStatus::PAID
                ? (float) $booking->payment->amount
                : ($newStatus === PaymentStatus::REFUNDED ? 0 : $booking->payment->amount_collected),
        ]);
        $this->logPaymentStatus($booking->payment, $oldStatus, $newStatus, Auth::id(), 'Admin/staff cập nhật trạng thái thanh toán.');

        // Trước đây khách không được báo gì khi admin/staff xác nhận thanh
        // toán hoặc xử lý hoàn tiền thủ công — chỉ đổi trạng thái âm thầm.
        if ($newStatus === PaymentStatus::PAID) {
            $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã được xác nhận thanh toán."));
        } elseif ($newStatus === PaymentStatus::REFUNDED) {
            $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã được hoàn tiền."));
        }

        return $booking->fresh('payment');
    }

    public function confirm(Booking $booking): Booking
    {
        if (! $booking->canConfirm()) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể xác nhận đơn ở trạng thái chờ xác nhận.'],
            ]);
        }

        $oldStatus = $booking->status;
        $booking->update(['status' => BookingStatus::CONFIRMED]);
        $this->logStatus($booking, $oldStatus, BookingStatus::CONFIRMED, Auth::id(), 'Admin/staff xác nhận đơn.');

        $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã được xác nhận."));

        return $booking->fresh();
    }

    /**
     * Admin/staff thêm dịch vụ cho đơn ĐANG lưu trú (checked_in) — vd khách
     * gọi thêm đồ ăn/giặt ủi giữa kỳ nghỉ. Trước đây dịch vụ chỉ gắn được
     * lúc tạo đơn ban đầu, không có cách nào thêm sau khi khách đã nhận
     * phòng. Cộng dồn vào booking.total_amount + payment.amount qua
     * applyExtraCharge() (xem đó để biết cách xử lý khi đơn đã thanh toán đủ).
     */
    public function addServiceItem(Booking $booking, int $serviceId, int $quantity): Booking
    {
        if ($booking->status !== BookingStatus::CHECKED_IN) {
            throw ValidationException::withMessages([
                'service_id' => ['Chỉ có thể thêm dịch vụ cho đơn đang lưu trú (đã check-in).'],
            ]);
        }

        $service = Service::where('status', 'active')->findOrFail($serviceId);

        // Quy định khung giờ phục vụ (VD: ăn sáng buffet chỉ 06:00-10:00) —
        // chỉ áp dụng cho luồng "gọi thêm dịch vụ" này (khách/staff đang thao
        // tác NGAY LÚC lưu trú, nên "bây giờ" chính là giờ sử dụng dịch vụ);
        // không áp dụng cho dịch vụ chọn sẵn lúc đặt phòng ban đầu, vì đó là
        // đặt trước cho cả kỳ nghỉ tương lai, không gắn với 1 thời điểm cụ thể.
        if (! $service->isAvailableAt()) {
            throw ValidationException::withMessages([
                'service_id' => ["Dịch vụ \"{$service->name}\" chỉ phục vụ trong khung giờ {$service->availabilityLabel()}, hiện tại là " . now('Asia/Ho_Chi_Minh')->format('H:i') . '.'],
            ]);
        }

        $quantity = max(1, $quantity);
        $subtotal = (float) $service->price * $quantity;

        return DB::transaction(function () use ($booking, $service, $quantity, $subtotal) {
            $booking->serviceItems()->create([
                'service_id' => $service->id,
                'quantity'   => $quantity,
                'unit_price' => $service->price,
                'subtotal'   => $subtotal,
            ]);

            $this->applyExtraCharge($booking, $subtotal, "Phát sinh thêm dịch vụ \"{$service->name}\" × {$quantity} trong lúc lưu trú.");

            return $booking->fresh(['serviceItems.service', 'payment']);
        });
    }

    /**
     * Admin/staff ghi nhận phụ phí phát sinh tùy ý (hư hỏng, trả phòng
     * trễ...) không gắn với dịch vụ nào trong catalog — khác addServiceItem()
     * ở chỗ đây là số tiền + lý do nhập tay, lưu riêng ở payments.surcharge_amount
     * (không tạo dòng booking_services). Cùng cơ chế cộng dồn applyExtraCharge().
     */
    public function addSurcharge(Booking $booking, float $amount, string $note): Booking
    {
        if ($booking->status !== BookingStatus::CHECKED_IN) {
            throw ValidationException::withMessages([
                'amount' => ['Chỉ có thể ghi nhận phụ phí phát sinh cho đơn đang lưu trú (đã check-in).'],
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Số tiền phụ phí phải lớn hơn 0.'],
            ]);
        }

        if (! $booking->payment) {
            throw ValidationException::withMessages([
                'amount' => ['Đơn này chưa có thông tin thanh toán.'],
            ]);
        }

        return DB::transaction(function () use ($booking, $amount, $note) {
            $booking->payment->increment('surcharge_amount', $amount);
            $booking->payment->update([
                'surcharge_note' => $booking->payment->surcharge_note
                    ? $booking->payment->surcharge_note . " | {$note} (+" . number_format($amount, 0, ',', '.') . 'đ)'
                    : "{$note} (+" . number_format($amount, 0, ',', '.') . 'đ)',
            ]);

            $this->applyExtraCharge($booking, $amount, "Phụ phí phát sinh: {$note}.");

            return $booking->fresh('payment');
        });
    }

    /**
     * Logic dùng chung khi cộng thêm tiền vào một đơn đã tồn tại (dịch vụ
     * thêm hoặc phụ phí): tăng total_amount/payment.amount theo đúng số
     * tiền phát sinh. Nếu đơn LÚC NÀY đã ở trạng thái đã thanh toán đủ
     * (PAID) — số tiền cũ không còn đủ cho tổng mới — mở lại về PENDING để
     * bắt buộc thu thêm phần chênh lệch trước khi cho trả phòng (xem
     * Booking::canCheckOut()); nếu đơn chưa PAID (đang unpaid/deposit_paid)
     * thì số tiền mới cứ cộng dồn bình thường, thu chung 1 lần như cũ.
     */
    private function applyExtraCharge(Booking $booking, float $amount, string $logNote): void
    {
        $booking->increment('total_amount', $amount);

        $payment = $booking->payment;

        if (! $payment) {
            return;
        }

        $wasPaid = $payment->status === PaymentStatus::PAID;

        $payment->increment('amount', $amount);

        // Trước đây chỉ ghi log + báo khách khi đơn đã PAID (phải mở lại
        // chờ thu thêm) — đơn đang unpaid/deposit_paid vẫn bị cộng thêm phí
        // nhưng khách không hề hay biết cho tới lúc trả phòng. Giờ luôn ghi
        // log + luôn báo khách, chỉ khác nhau ở việc có đổi trạng thái hay không.
        if ($wasPaid) {
            $payment->update(['status' => PaymentStatus::PENDING]);
            $this->logPaymentStatus($payment, PaymentStatus::PAID, PaymentStatus::PENDING, Auth::id(), $logNote . ' Đơn đã thanh toán đủ trước đó — mở lại chờ thu thêm phần chênh lệch.');
        } else {
            $this->logPaymentStatus($payment, $payment->status, $payment->status, Auth::id(), $logNote);
        }

        $amountText = number_format($amount, 0, ',', '.') . 'đ';
        $message = $wasPaid
            ? "Đơn {$booking->booking_code} phát sinh thêm phí {$amountText}, vui lòng thanh toán phần còn lại khi trả phòng."
            : "Đơn {$booking->booking_code} phát sinh thêm phí {$amountText}, đã cộng vào tổng tiền cần thanh toán.";
        $booking->user?->notify(new BookingStatusChanged($booking, $message));
    }

    /**
     * Check-in thật — gán số phòng vật lý cụ thể cho từng dòng đơn (đúng
     * số lượng `quantity` của dòng, phòng phải cùng room_type và hiện
     * không có khách). $roomAssignments khóa theo booking_item_id, giá
     * trị là mảng room_id.
     *
     * @param  array<int, array<int, int>>  $roomAssignments
     * @return array{booking: Booking, early_checkin_fee: ?float}
     *
     * @throws ValidationException
     */
    public function checkIn(Booking $booking, array $roomAssignments): array
    {
        if (! $booking->canCheckIn()) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể check-in đơn ở trạng thái đã xác nhận và đã đặt cọc/thanh toán.'],
            ]);
        }

        return DB::transaction(function () use ($booking, $roomAssignments) {
            foreach ($booking->bookingItems as $item) {
                $roomIds = array_values(array_unique($roomAssignments[$item->id] ?? []));

                if (count($roomIds) !== $item->quantity) {
                    throw ValidationException::withMessages([
                        'rooms' => ["Phải chọn đúng {$item->quantity} phòng cho loại phòng \"{$item->roomType->name}\"."],
                    ]);
                }

                foreach ($roomIds as $roomId) {
                    // lockForUpdate() để chặn 2 giao dịch check-in cùng lúc
                    // đọc cùng phòng trước khi ai commit — nếu không, cả hai
                    // đều có thể pass isOccupied() và gán trùng 1 phòng vật lý.
                    $room = Room::where('room_type_id', $item->room_type_id)->lockForUpdate()->find($roomId);

                    if (! $room) {
                        throw ValidationException::withMessages([
                            'rooms' => ['Phòng đã chọn không thuộc đúng loại phòng của dòng đơn này.'],
                        ]);
                    }

                    if ($room->isOccupied()) {
                        throw ValidationException::withMessages([
                            'rooms' => ["Phòng \"{$room->room_number}\" hiện đang có khách."],
                        ]);
                    }

                    BookingItemRoom::create(['booking_item_id' => $item->id, 'room_id' => $roomId]);
                }
            }

            $oldStatus = $booking->status;
            $booking->update(['status' => BookingStatus::CHECKED_IN]);
            $this->logStatus($booking, $oldStatus, BookingStatus::CHECKED_IN, Auth::id(), 'Khách nhận phòng.');

            $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã check-in."));

            // Nhận phòng TRƯỚC giờ check_in_time tiêu chuẩn của khách sạn ⇒
            // tự động tính phụ phí = % (cấu hình ở HotelInfo) × tổng giá
            // phòng/đêm đầu tiên của cả đơn — tái dùng addSurcharge() (đã
            // đòi hỏi status CHECKED_IN, vừa mới đổi ở trên) nên phụ phí này
            // cộng dồn vào đơn giống hệt phụ phí phát sinh thủ công, và nếu
            // đơn đã thanh toán đủ từ trước thì tự mở lại chờ thu thêm. Trả
            // fee về ngoài để staff/admin thấy ngay số tiền vừa tự động
            // cộng — trước đây thu âm thầm, không có gì báo cho nhân viên
            // biết đơn vừa bị cộng thêm phí và cần thu thêm khi trả phòng.
            $fee = $this->applyEarlyCheckinSurchargeIfNeeded($booking);

            return ['booking' => $booking->fresh(['bookingItems.rooms', 'payment']), 'early_checkin_fee' => $fee];
        });
    }

    /**
     * Xem checkIn() — tách riêng cho dễ đọc. Không thu phí nếu khách sạn
     * chưa cấu hình giờ nhận phòng chuẩn hoặc % phụ phí = 0 (mặc định).
     *
     * @return ?float  Số tiền phụ phí vừa cộng, null nếu không áp dụng.
     */
    private function applyEarlyCheckinSurchargeIfNeeded(Booking $booking): ?float
    {
        $hotel = HotelInfo::instance();

        if (! $hotel->check_in_time || (float) $hotel->early_checkin_surcharge_percent <= 0) {
            return null;
        }

        // Chỉ tính là "nhận phòng sớm" khi hôm nay ĐÚNG là ngày check_in đã
        // đặt — nếu không, khách nhận phòng TRỄ hơn ngày đã đặt (VD đặt
        // check_in hôm qua, hôm nay mới đến) vẫn có thể rơi vào trước giờ
        // check_in_time chuẩn (VD 9h sáng, chuẩn 14h) nhưng đó là trễ chứ
        // không phải sớm — không được tính phụ phí trong trường hợp đó.
        if (! $booking->isCheckInDateToday()) {
            return null;
        }

        // So sánh chuỗi giờ thuần túy (H:i:s), không qua Carbon instant —
        // cùng cách làm an toàn đã dùng ở Service::isAvailableAt(), tránh
        // lỗi lệch múi giờ (app chạy UTC, khách sạn vận hành giờ VN).
        $nowVn = now('Asia/Ho_Chi_Minh')->format('H:i:s');

        if ($nowVn >= $hotel->check_in_time) {
            return null;
        }

        // Dùng nightly_total của ĐÊM ĐẦU TIÊN trong price_breakdown (đã lưu
        // sẵn lúc đặt, gồm cả điều chỉnh giá theo mùa + phụ thu cuối tuần
        // của đúng đêm đó) thay vì item->price_per_night (giá gốc CHƯA điều
        // chỉnh) — nếu không, phụ phí sẽ tính sai lệch khi đêm đầu tiên rơi
        // vào đợt giá mùa cao điểm hoặc cuối tuần.
        $firstNightTotal = $booking->bookingItems->sum(function (BookingItem $item) {
            $firstNight = $item->price_breakdown[0]['nightly_total'] ?? $item->price_per_night;

            return (float) $firstNight * $item->quantity;
        });
        $fee = round($firstNightTotal * (float) $hotel->early_checkin_surcharge_percent / 100);

        if ($fee > 0) {
            $this->addSurcharge(
                $booking,
                $fee,
                "Nhận phòng sớm trước giờ tiêu chuẩn " . substr($hotel->check_in_time, 0, 5) . " (lúc " . substr($nowVn, 0, 5) . ")"
            );

            return $fee;
        }

        return null;
    }

    /**
     * Check-out — chuyển trạng thái + tự động đánh dấu các phòng đã gán
     * cần dọn (dirty), để buồng phòng biết cần xử lý trước khi nhận khách kế tiếp.
     *
     * @return array{booking: Booking, late_checkout_fee: ?float}
     *
     * @throws ValidationException
     */
    public function checkOut(Booking $booking): array
    {
        if (! $booking->canCheckOut()) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể trả phòng khi đơn đang lưu trú VÀ đã thanh toán đủ (kể cả phần phát sinh thêm nếu có).'],
            ]);
        }

        // Định nghĩa "trả phòng sớm" nằm ở Booking::isEarlyCheckoutToday() —
        // dùng chung với dòng ghi log bên dưới, tránh nhiều nơi tự tính lại
        // "hôm nay" mỗi nơi một kiểu rồi lệch nhau (xem docblock ở model để
        // biết vì sao phải quy về ngày lịch thuần túy).
        $isEarly = $booking->isEarlyCheckoutToday();

        return DB::transaction(function () use ($booking, $isEarly) {
            // Phụ phí trả phòng MUỘN (đúng ngày check_out đã đặt nhưng sau
            // giờ chuẩn) phải tính TRƯỚC khi đổi status sang CHECKED_OUT —
            // applyLateCheckoutSurchargeIfNeeded() tái dùng addSurcharge(),
            // đòi hỏi booking đang CHECKED_IN. Không áp dụng cho trả phòng
            // sớm (isEarly true nghĩa là sai ngày, không phải trễ giờ).
            $fee = $isEarly ? null : $this->applyLateCheckoutSurchargeIfNeeded($booking);

            $oldStatus = $booking->status;
            $booking->update(['status' => BookingStatus::CHECKED_OUT]);

            $note = $isEarly
                ? "Khách trả phòng SỚM hơn dự kiến (còn " . $booking->nightsRemainingForEarlyCheckout() . " đêm chưa sử dụng, ngày đặt trả phòng: {$booking->check_out->format('d/m/Y')})."
                : 'Khách trả phòng.';
            $this->logStatus($booking, $oldStatus, BookingStatus::CHECKED_OUT, Auth::id(), $note);

            $roomIds = BookingItemRoom::whereIn('booking_item_id', $booking->bookingItems->pluck('id'))->pluck('room_id');
            Room::whereIn('id', $roomIds)->update(['housekeeping_status' => 'dirty']);

            $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã trả phòng. Cảm ơn bạn đã lưu trú!"));

            return ['booking' => $booking->fresh(['payment']), 'late_checkout_fee' => $fee];
        });
    }

    /**
     * Xem checkOut() — tách riêng cho dễ đọc, đối xứng với
     * applyEarlyCheckinSurchargeIfNeeded(). Không thu phí nếu khách sạn chưa
     * cấu hình giờ trả phòng chuẩn hoặc % phụ phí = 0 (mặc định).
     *
     * @return ?float  Số tiền phụ phí vừa cộng, null nếu không áp dụng.
     */
    private function applyLateCheckoutSurchargeIfNeeded(Booking $booking): ?float
    {
        $hotel = HotelInfo::instance();

        if (! $hotel->check_out_time || (float) $hotel->late_checkout_surcharge_percent <= 0) {
            return null;
        }

        // Chỉ tính là "trả phòng muộn" khi hôm nay ĐÚNG là ngày check_out đã
        // đặt — trả phòng sớm hơn ngày đặt (isEarly ở checkOut()) không đi
        // vào nhánh này, và trả phòng sau cả ngày check_out (ở lại thêm
        // ngày) là tình huống khác, ngoài phạm vi phụ phí trả phòng muộn.
        if (! $booking->isCheckOutDateToday()) {
            return null;
        }

        // So sánh chuỗi giờ thuần túy (H:i:s), không qua Carbon instant —
        // cùng cách làm an toàn đã dùng ở applyEarlyCheckinSurchargeIfNeeded().
        $nowVn = now('Asia/Ho_Chi_Minh')->format('H:i:s');

        if ($nowVn <= $hotel->check_out_time) {
            return null;
        }

        // Dùng nightly_total của ĐÊM CUỐI CÙNG trong price_breakdown (đã lưu
        // sẵn lúc đặt, gồm cả điều chỉnh giá theo mùa + phụ thu cuối tuần
        // của đúng đêm đó) — đối xứng với đêm đầu tiên dùng cho phụ phí
        // nhận phòng sớm.
        $lastNightTotal = $booking->bookingItems->sum(function (BookingItem $item) {
            $breakdown = $item->price_breakdown ?? [];
            $lastNight = $breakdown !== [] ? end($breakdown)['nightly_total'] ?? $item->price_per_night : $item->price_per_night;

            return (float) $lastNight * $item->quantity;
        });
        $fee = round($lastNightTotal * (float) $hotel->late_checkout_surcharge_percent / 100);

        if ($fee > 0) {
            $this->addSurcharge(
                $booking,
                $fee,
                "Trả phòng muộn sau giờ tiêu chuẩn " . substr($hotel->check_out_time, 0, 5) . " (lúc " . substr($nowVn, 0, 5) . ")"
            );

            return $fee;
        }

        return null;
    }

    public function complete(Booking $booking): Booking
    {
        if (! $booking->canComplete()) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể đánh dấu hoàn thành đơn ở trạng thái phù hợp và đã thanh toán đủ.'],
            ]);
        }

        $oldStatus = $booking->status;
        $booking->update(['status' => BookingStatus::COMPLETED]);
        $this->logStatus($booking, $oldStatus, BookingStatus::COMPLETED, Auth::id(), 'Admin/staff đánh dấu đơn hoàn thành.');

        $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã hoàn thành. Cảm ơn bạn đã lưu trú!"));

        return $booking->fresh();
    }

    /**
     * @return array{booking: Booking, refund_ok: bool} `refund_ok` = false
     *         nghĩa là đơn đã hủy thành công nhưng hoàn tiền tự động qua
     *         cổng KHÔNG thực hiện được (cần xử lý hoàn tiền thủ công) —
     *         nơi gọi PHẢI báo rõ điều này cho admin/staff, không được coi
     *         "hủy đơn" và "đã hoàn tiền" là một.
     */
    public function cancelByAdmin(Booking $booking): array
    {
        if (! $booking->canCancelByAdmin()) {
            throw ValidationException::withMessages([
                'status' => ['Không thể hủy đơn ở trạng thái hiện tại.'],
            ]);
        }

        $wasCheckedIn = $booking->status === BookingStatus::CHECKED_IN;

        DB::transaction(function () use ($booking, $wasCheckedIn) {
            $oldStatus = $booking->status;

            $booking->update(['status' => BookingStatus::CANCELLED]);
            $this->logStatus($booking, $oldStatus, BookingStatus::CANCELLED, Auth::id(), 'Admin/staff hủy đơn.');

            $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã bị hủy bởi khách sạn."));

            // Hủy khi đang lưu trú (CHECKED_IN) — khách rời phòng giữa chừng,
            // cần giải phóng phòng vật lý để buồng phòng biết cần dọn trước
            // khi nhận khách kế tiếp (cùng hành vi với checkOut()).
            if ($wasCheckedIn) {
                $roomIds = BookingItemRoom::whereIn('booking_item_id', $booking->bookingItems->pluck('id'))->pluck('room_id');
                Room::whereIn('id', $roomIds)->update(['housekeeping_status' => 'dirty']);
            }
        });

        // Gọi ngoài transaction ở trên — đây là 1 lời gọi HTTP ra ngoài
        // (API hoàn tiền VNPay), không nên giữ transaction DB mở trong lúc
        // chờ network.
        $refundOk = $this->attemptRefund($booking, Auth::id());

        return ['booking' => $booking->fresh(['payment']), 'refund_ok' => $refundOk];
    }

    // ----------------------------------------------------------------
    // PRIVATE
    // ----------------------------------------------------------------

    /**
     * Kiểm tra sức chứa + giới hạn trẻ em theo TỪNG dòng loại phòng (capacity
     * riêng, không gộp chung các dòng khác trong đơn) — dùng chung cho cả
     * create() (khách tự đặt) và createByAdmin() (admin/staff tạo hộ), 2 nơi
     * trước đây lặp lại cùng 1 đoạn validate gần như y hệt.
     *
     * Quy định độ tuổi: "trẻ em" (field `children`) = 6-11 tuổi, tính vào
     * sức chứa phòng như người lớn + giới hạn tối đa self::MAX_CHILDREN_PER_ROOM/phòng;
     * "trẻ sơ sinh" (field `infants`) = 0-5 tuổi, miễn phí và KHÔNG tính vào
     * sức chứa/giới hạn này; từ 12 tuổi trở lên khai vào ô người lớn.
     *
     * @param  array<int, array{room_type_id: mixed, quantity: mixed, adults?: mixed, children?: mixed}>  $items
     */
    private function validateGuestCapacity(array $items, \Illuminate\Support\Collection $roomTypes): void
    {
        foreach ($items as $index => $item) {
            $roomType = $roomTypes[(int) $item['room_type_id']];
            $quantity = (int) $item['quantity'];
            $adults   = (int) ($item['adults'] ?? 1);
            $children = (int) ($item['children'] ?? 0);
            $capacity = $roomType->capacity * $quantity;
            $maxChildren = self::MAX_CHILDREN_PER_ROOM * $quantity;

            if ($children > $maxChildren) {
                throw ValidationException::withMessages([
                    "items.{$index}.children" => ["Phòng \"{$roomType->name}\" tối đa " . self::MAX_CHILDREN_PER_ROOM . " trẻ em (6-11 tuổi)/phòng × {$quantity} phòng = {$maxChildren}, nhưng khai báo {$children} trẻ em."],
                ]);
            }

            if ($adults + $children > $capacity) {
                throw ValidationException::withMessages([
                    "items.{$index}.adults" => ["Phòng \"{$roomType->name}\" tối đa {$capacity} khách ({$roomType->capacity} khách/phòng × {$quantity} phòng), nhưng khai báo {$adults} người lớn + {$children} trẻ em (trẻ sơ sinh dưới 6 tuổi không tính vào sức chứa)."],
                ]);
            }
        }
    }

    /**
     * Kiểm tra trước (ngoài transaction, KHÔNG lock) TẤT CẢ các dòng loại
     * phòng xem còn đủ chỗ hay không, gom hết các dòng đã hết phòng vào
     * MỘT thông báo lỗi duy nhất — trước đây mỗi lần submit chỉ báo dòng
     * ĐẦU TIÊN hết phòng, khách phải sửa rồi submit lại nhiều lần mới biết
     * hết các dòng còn lại cũng hết phòng.
     *
     * Đây chỉ là kiểm tra phục vụ hiển thị — KHÔNG thay thế cho lượt
     * re-check có lockForUpdate() bên trong DB::transaction() ngay sau khi
     * gọi hàm này (giữ nguyên, chống race condition khi 2 khách đặt cùng
     * lúc trúng đúng khoảnh khắc giữa 2 lượt kiểm tra).
     *
     * @param  array<int, array{room_type_id: mixed, quantity: mixed}>  $items
     */
    private function checkAvailabilityForAllItems(
        array $items,
        \Illuminate\Support\Collection $roomTypes,
        string $checkIn,
        string $checkOut,
        ?string $holdSessionId = null,
    ): void {
        $unavailable = [];

        foreach ($items as $item) {
            $roomType = $roomTypes[(int) $item['room_type_id']];
            $quantity = (int) $item['quantity'];

            if (! $this->availabilityService->canBook($roomType->id, $checkIn, $checkOut, $quantity, $holdSessionId)) {
                $unavailable[] = "Phòng \"{$roomType->name}\" đã hết trong khoảng thời gian này.";
            }
        }

        if ($unavailable !== []) {
            throw ValidationException::withMessages(['items' => $unavailable]);
        }
    }

    /**
     * Tự động hoàn tiền khi hủy đơn đã thanh toán đủ (PaymentStatus::canRefund()
     * — chỉ áp dụng cho PAID, không áp dụng cho DEPOSIT_PAID theo chính sách
     * "cọc giữ chỗ"). Nếu thanh toán qua VNPay thì gọi API hoàn tiền thật của
     * cổng — CHỈ hoàn đúng phần `amount_collected` (số tiền thật sự đã thu
     * qua VNPay), KHÔNG phải `amount` (có thể đã bị cộng thêm phụ phí thu
     * bằng tiền mặt ngoài cổng — xem applyExtraCharge()), tránh yêu cầu hoàn
     * nhiều hơn số VNPay thực nhận. Các phương thức khác (chuyển khoản, tiền
     * mặt...) chỉ đánh dấu trạng thái vì tiền được hoàn thủ công ngoài hệ thống.
     *
     * @return bool true nếu đã hoàn xong (hoặc không cần hoàn tự động qua
     *              cổng — chuyển khoản/tiền mặt); false nếu ĐÁNG LẼ phải tự
     *              động hoàn qua cổng nhưng không thực hiện được (lỗi mạng,
     *              cổng từ chối, hoặc thiếu thông tin giao dịch) — nơi gọi
     *              hàm này cần báo rõ cho người dùng biết để xử lý thủ công,
     *              KHÔNG được coi là đã hoàn tiền thành công.
     */
    private function attemptRefund(Booking $booking, ?int $actorId): bool
    {
        $payment = $booking->payment;

        if (! $payment) {
            return true;
        }

        $oldStatus = $payment->status;

        if ($payment->method === PaymentMethod::ONLINE_VNPAY) {
            $collected = (float) $payment->amount_collected;

            // Với VNPay, xét theo amount_collected (tiền THẬT đã thu qua
            // cổng) thay vì payment->canRefund() (dựa trên status hiện tại)
            // — applyExtraCharge() có thể đã mở lại PAID→PENDING do phát
            // sinh phụ phí SAU khi thanh toán xong, nhưng tiền cũ vẫn đang
            // nằm ở VNPay và vẫn phải hoàn khi hủy đơn, bất kể status lúc
            // này không còn là PAID nữa. Nếu chưa từng thu được đồng nào
            // (collected <= 0, kể cả trường hợp đã hoàn hết ở lần trước) thì
            // không có gì để hoàn.
            if ($collected <= 0) {
                return true;
            }

            if (! $payment->gateway_transaction_no) {
                $this->logPaymentStatus($payment, $oldStatus, $oldStatus, $actorId, 'Thanh toán VNPay thiếu thông tin giao dịch cổng (chưa từng được VNPay xác nhận thật) — không thể tự động hoàn tiền, cần xử lý hoàn tiền thủ công.');

                return false;
            }

            // transaction_code/gateway_transaction_no chỉ lưu được giao dịch
            // VNPay GẦN NHẤT — nếu payment đã trải qua nhiều chu kỳ thanh
            // toán VNPay riêng biệt (trả đủ → phụ phí mở lại PENDING → trả
            // tiếp qua VNPay lần 2), collected có thể lớn hơn số tiền giao
            // dịch hiện tại thực thu (last_gateway_amount). Chỉ được yêu cầu
            // API hoàn tối đa bằng số tiền của ĐÚNG giao dịch đang lưu, tránh
            // VNPay từ chối/hoàn sai do vượt quá số giao dịch gốc.
            $refundable = min($collected, (float) ($payment->last_gateway_amount ?? $collected));
            $strandedFromEarlierTxn = round($collected - $refundable, 2);

            try {
                $response = $this->vnPayService->refund(
                    $payment->transaction_code,
                    $refundable,
                    $payment->gateway_transaction_no,
                    $payment->gateway_paid_at?->format('YmdHis') ?? now()->format('YmdHis'),
                    'Hoan tien don ' . $booking->booking_code,
                    (string) ($actorId ?? 0),
                    request()?->ip() ?? '127.0.0.1',
                );
            } catch (\Throwable $e) {
                $this->logPaymentStatus($payment, $oldStatus, $oldStatus, $actorId, 'Gọi API hoàn tiền VNPay lỗi: ' . $e->getMessage() . ' — cần xử lý hoàn tiền thủ công.');

                return false;
            }

            if (($response['vnp_ResponseCode'] ?? null) === '00') {
                if ($strandedFromEarlierTxn > 0) {
                    // Đã hoàn thành công phần thuộc giao dịch gần nhất, nhưng
                    // vẫn còn phần tiền của (các) giao dịch VNPay TRƯỚC ĐÓ
                    // không còn thông tin để tự động hoàn — giữ nguyên phần
                    // còn lại ở amount_collected (KHÔNG đánh dấu REFUNDED) và
                    // báo cần xử lý thủ công cho đúng phần đó.
                    $payment->update(['amount_collected' => $strandedFromEarlierTxn, 'last_gateway_amount' => null]);
                    $this->logPaymentStatus($payment, $oldStatus, $oldStatus, $actorId, 'Hoàn tiền tự động qua VNPay thành công cho '
                        . number_format($refundable, 0, ',', '.') . 'đ (giao dịch gần nhất) — còn '
                        . number_format($strandedFromEarlierTxn, 0, ',', '.') . 'đ từ (các) giao dịch trước đó không còn thông tin cổng để tự động hoàn, cần xử lý hoàn tiền thủ công.');

                    return false;
                }

                $payment->update(['status' => PaymentStatus::REFUNDED, 'amount_collected' => 0, 'last_gateway_amount' => null]);
                $this->logPaymentStatus($payment, $oldStatus, PaymentStatus::REFUNDED, $actorId, 'Hoàn tiền tự động qua VNPay thành công.');

                return true;
            }

            $this->logPaymentStatus($payment, $oldStatus, $oldStatus, $actorId, 'Hoàn tiền tự động qua VNPay thất bại (mã ' . ($response['vnp_ResponseCode'] ?? '?') . ') — cần xử lý hoàn tiền thủ công.');

            return false;
        }

        // Các phương thức thủ công (chuyển khoản, tiền mặt...): dựa theo
        // amount_collected (được updatePaymentStatus() ghi nhận khi đánh dấu
        // PAID) thay vì status hiện tại — applyExtraCharge() có thể đã mở lại
        // PAID→PENDING do phát sinh phụ phí SAU khi thanh toán xong, nhưng
        // khoản tiền mặt/chuyển khoản cũ khách đã nộp vẫn cần được hoàn khi
        // hủy, bất kể status lúc này không còn là PAID nữa (tiền cọc giữ chỗ
        // — chưa từng có amount_collected — vẫn không tự động hoàn, đúng
        // chính sách cũ).
        if ((float) $payment->amount_collected <= 0) {
            return true;
        }

        $payment->update(['status' => PaymentStatus::REFUNDED, 'amount_collected' => 0]);
        $this->logPaymentStatus($payment, $oldStatus, PaymentStatus::REFUNDED, $actorId, 'Tự động hoàn tiền khi hủy đơn.');

        return true;
    }

    private function logStatus(
        Booking $booking,
        ?BookingStatus $from,
        BookingStatus $to,
        ?int $changedById = null,
        ?string $note = null,
    ): void {
        BookingStatusLog::create([
            'booking_id'  => $booking->id,
            'changed_by'  => $changedById,
            'from_status' => $from?->value,
            'to_status'   => $to->value,
            'note'        => $note,
        ]);
    }

    private function logPaymentStatus(
        Payment $payment,
        ?PaymentStatus $from,
        PaymentStatus $to,
        ?int $changedById = null,
        ?string $note = null,
    ): void {
        PaymentStatusLog::create([
            'payment_id'  => $payment->id,
            'changed_by'  => $changedById,
            'from_status' => $from?->value,
            'to_status'   => $to->value,
            'note'        => $note,
        ]);
    }

    private function generateCode(): string
    {
        do {
            $code = 'HOMI-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}
