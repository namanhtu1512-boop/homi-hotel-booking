<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundRequestStatus;
use App\Events\ExtraBedUnavailable;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingItemRoom;
use App\Models\BookingStatusLog;
use App\Models\ExtraBedRequest;
use App\Models\HotelInfo;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\Room;
use App\Models\PaymentStatusLog;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingStatusChanged;
use App\Notifications\NewBookingReceived;
use App\Notifications\OrphanedPaymentNeedsRefund;
use App\Notifications\OverdueCheckout;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Trẻ em (6-11 tuổi, cột `children`) tối đa mỗi phòng — trẻ sơ sinh
     * (0-5 tuổi, cột `infants`) miễn phí và không tính vào sức chứa phòng,
     * nhưng vẫn bị giới hạn số lượng riêng (self::MAX_INFANTS_PER_ROOM); từ
     * 12 tuổi trở lên khai vào ô người lớn.
     */
    private const MAX_CHILDREN_PER_ROOM = 2;

    /**
     * Trẻ sơ sinh (0-5 tuổi, cột `infants`) tối đa mỗi phòng — áp dụng
     * ĐỒNG NHẤT cho mọi category (không có giường phụ/ngoại lệ nào cho trẻ
     * sơ sinh, khác với trẻ em 6-11 tuổi). Miễn phí, không tính vào sức chứa
     * phòng hay $capacity, chỉ giới hạn số lượng thuần túy.
     */
    private const MAX_INFANTS_PER_ROOM = 2;

    /**
     * Khung thời gian giữ chỗ để khách hoàn tất cọc 30% hoặc thanh toán đủ
     * kể từ lúc tạo đơn (trạng thái pending_deposit) — quá hạn mà chưa làm
     * gì thì đơn bị tự động hủy, nhả phòng lại (xem
     * cancelExpiredDepositBookings(), CancelExpiredDepositBookings command).
     *
     * Bằng đúng thời gian giữ chỗ tạm (RoomHoldService::TTL_MINUTES) ở bước
     * điền form trước đó — để khách có cảm giác liền mạch "giữ 15 phút" từ
     * lúc chọn phòng tới lúc đặt cọc, không nhảy vọt lên 30 phút.
     *
     * initiateVnpayPayment() cấp cho VNPay đúng phần thời gian CÒN LẠI của
     * hold này (không bao giờ vượt quá config('services.vnpay.txn_expire_minutes')
     * dù hold còn dư nhiều hơn) — nên đồng hồ đếm ngược khách thấy bên VNPay
     * chính là phần còn lại của đồng hồ giữ chỗ, không "reset" về một cửa sổ
     * mới mỗi lần bấm thanh toán. Lớp bảo vệ an toàn cho IPN tới trễ SAU khi
     * hold (= phiên VNPay) hết hạn nằm ở BookingStatus::EXPIRED_PENDING_CHECK
     * (processBookingExpiry()), không phải bằng cách nới hold dài hơn VNPay.
     */
    public const DEPOSIT_HOLD_MINUTES = 15;

    public function __construct(
        private AvailabilityService $availabilityService,
        private PricingService $pricingService,
        private PromotionService $promotionService,
        private RoomHoldService $roomHoldService,
        private VNPayService $vnPayService,
        private IncidentalInvoiceService $incidentalInvoiceService,
        private ExtraBedInventoryService $extraBedInventoryService,
        private RoomService $roomService,
        private GroupDiscountPolicyService $groupDiscountPolicyService,
        private GroupDiscountRequestService $groupDiscountRequestService,
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

        $roomCount = array_sum(array_column($data['items'], 'quantity'));

        $booking = DB::transaction(function () use ($data, $roomTypes, $roomCount) {
            RoomType::whereIn('id', $roomTypes->keys()->sort()->values())->lockForUpdate()->get();

            $nights = null;
            $total  = 0;
            $lines  = [];

            // Dòng nào cần giường phụ (đã qua validateGuestCapacity() nên chắc
            // chắn category hỗ trợ + khách/nhân viên đã tick) — cùng cơ chế
            // pool dùng chung với create() (khách tự đặt), khác trước đây
            // createByAdmin() bỏ qua hoàn toàn extra_bed dù validateGuestCapacity()
            // đã chấp nhận field này (checkbox không có tác dụng thật).
            $extraBedByIndex = [];

            foreach ($data['items'] as $item) {
                $roomType = $roomTypes[(int) $item['room_type_id']];
                $quantity = (int) $item['quantity'];
                $adults   = (int) ($item['adults'] ?? 1);
                $children = (int) ($item['children'] ?? 0);

                if (! $this->availabilityService->canBook($roomType->id, $data['check_in'], $data['check_out'], $quantity)) {
                    throw ValidationException::withMessages([
                        'items' => ["Phòng \"{$roomType->name}\" đã hết trong khoảng thời gian này."],
                    ]);
                }

                $pricing = $this->pricingService->calculate($roomType, $data['check_in'], $data['check_out'], $quantity, $children);
                $nights ??= $pricing['nights'];
                $total  += $pricing['total_price'];

                $extraBedCount = $this->extraBedsNeeded($roomType, $quantity, $adults, $children);
                if ($extraBedCount > 0) {
                    $extraBedByIndex[count($lines)] = $extraBedCount;
                }

                $lines[] = [
                    'room_type_id'        => $roomType->id,
                    'quantity'            => $quantity,
                    'adults'              => $adults,
                    'children'            => $children,
                    'infants'             => (int) ($item['infants'] ?? 0),
                    'extra_beds'          => 0, // chỉ set thật sau khi biết tồn kho giường phụ, xem dưới
                    'extra_bed_surcharge' => 0, // idem — cộng vào $total dưới nếu được cấp
                    'price_per_night'     => $pricing['unit_price'],
                    'nights'              => $pricing['nights'],
                    'subtotal'            => $pricing['room_subtotal'],
                    'child_surcharge'     => $pricing['child_surcharge'],
                    'price_breakdown'     => $pricing['nightly_breakdown'],
                ];
            }

            $extraBedsNeeded           = array_sum($extraBedByIndex);
            $pendingConsultation       = false;
            $extraBedAvailableSnapshot = 0;

            if ($extraBedsNeeded > 0) {
                HotelInfo::query()->lockForUpdate()->first();
                $extraBedAvailableSnapshot = $this->extraBedInventoryService->countAvailable($data['check_in'], $data['check_out']);

                if ($extraBedAvailableSnapshot >= $extraBedsNeeded) {
                    foreach ($extraBedByIndex as $idx => $count) {
                        $roomType        = $roomTypes[(int) $lines[$idx]['room_type_id']];
                        $extraBedPricing = $this->pricingService->calculate(
                            $roomType, $data['check_in'], $data['check_out'],
                            $lines[$idx]['quantity'], $lines[$idx]['children'], $count
                        );

                        $lines[$idx]['extra_beds']         = $count;
                        $lines[$idx]['extra_bed_surcharge'] = $extraBedPricing['extra_bed_surcharge'];
                        $total += $extraBedPricing['extra_bed_surcharge'];
                    }
                } else {
                    // Không đủ — đơn KHÔNG bị chặn, chuyển "chờ tư vấn" giống hệt
                    // create() (khách tự đặt) thay vì hết phòng thật sự.
                    $pendingConsultation = true;
                }
            }

            // Ưu đãi đoàn/nhóm theo bậc số phòng (GroupDiscountPolicy) — tự động
            // áp ngay, không cần duyệt, vì admin đã duyệt sẵn qua chính sách
            // (xem GroupDiscountRequestService, ghi nhận lại bên dưới sau khi
            // có $booking để trace "nhân viên nào áp dụng ưu đãi gì").
            $groupDiscountPolicy = $this->groupDiscountPolicyService->matchTierFor($roomCount);
            $groupDiscount = $groupDiscountPolicy
                ? (int) round($total * (float) $groupDiscountPolicy->discount_percent / 100)
                : 0;

            // Mã giảm giá admin/staff tự nhập khi tạo đơn thủ công — tính trên
            // phần còn lại SAU ưu đãi tier tự động ở trên, cùng cách stack tuần
            // tự đã dùng ở create() (khách tự đặt), để không giảm vượt quá tổng
            // đơn dù cộng dồn cả 2 loại ưu đãi.
            $promotions   = collect();
            $promoDiscount = 0;
            $promoLines   = [];

            if (! empty($data['promo_codes'])) {
                $promoCustomer = User::find($data['user_id'] ?? null);
                $promotions = $this->promotionService->findValidManyByCodes($data['promo_codes'], $promoCustomer);

                $remaining = $total - $groupDiscount;
                foreach ($promotions as $promotion) {
                    $lineDiscount = min((int) $promotion->discountFor($remaining), $remaining);
                    $promoDiscount += $lineDiscount;
                    $remaining     -= $lineDiscount;
                    $promoLines[]   = ['promotion_id' => $promotion->id, 'discount_amount' => $lineDiscount];
                }
            }

            $discount = $groupDiscount + $promoDiscount;

            $booking = Booking::create([
                'user_id'        => $data['user_id'] ?? null,
                'promotion_id'   => $promotions->first()?->id,
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
                'national_id'    => $data['national_id'] ?? null,
                'note'           => $data['note'] ?? null,
                'total_amount'   => $total - $discount,
                'discount_amount'=> $discount,
                'status'         => $pendingConsultation ? BookingStatus::PENDING_CONSULTATION : BookingStatus::PENDING_DEPOSIT,
                'deposit_expires_at' => $pendingConsultation ? null : now()->addMinutes(self::DEPOSIT_HOLD_MINUTES),
            ]);

            if ($pendingConsultation) {
                $this->logStatus($booking, null, BookingStatus::PENDING_CONSULTATION, Auth::id(), "Giường phụ không đủ trong khoảng ngày đã chọn (cần {$extraBedsNeeded}, còn {$extraBedAvailableSnapshot}) — chờ khách/nhân viên chọn phương án.");
            } else {
                $this->logStatus($booking, null, BookingStatus::PENDING_DEPOSIT, Auth::id(), 'Admin/staff tạo đơn thủ công — chờ cọc 30% hoặc thanh toán đủ trong ' . self::DEPOSIT_HOLD_MINUTES . ' phút, quá hạn tự hủy.');
            }

            // Chỉ báo được nếu đơn có gắn tài khoản khách hàng (đơn nhóm/điện
            // thoại đôi khi không có, xem $data['user_id'] ?? null ở trên).
            $customerMessage = $pendingConsultation
                ? "Đơn {$booking->booking_code} hiện đang chờ tư vấn — số giường phụ yêu cầu tạm thời không đủ trong khoảng ngày này. Nhân viên sẽ liên hệ để chọn phương án."
                : "Đơn {$booking->booking_code} đã được tạo — vui lòng đặt cọc 30% hoặc thanh toán đủ trong " . self::DEPOSIT_HOLD_MINUTES . ' phút, nếu không đơn sẽ tự động hủy.';
            $booking->user?->notify(new BookingStatusChanged($booking, $customerMessage));

            $createdItems = [];
            foreach ($lines as $idx => $line) {
                $createdItems[$idx] = $booking->bookingItems()->create($line);
            }

            if ($pendingConsultation) {
                $firstIndex = array_key_first($extraBedByIndex);

                ExtraBedRequest::create([
                    'booking_id'           => $booking->id,
                    'booking_item_id'      => $createdItems[$firstIndex]->id,
                    'requested_extra_beds' => $extraBedsNeeded,
                    'available_extra_beds' => $extraBedAvailableSnapshot,
                    'status'               => 'pending',
                ]);
            }

            foreach ($promoLines as $promoLine) {
                $booking->promotions()->attach($promoLine['promotion_id'], ['discount_amount' => $promoLine['discount_amount']]);
            }

            $booking->payment()->create([
                'amount' => $total - $discount,
                'status' => PaymentStatus::UNPAID,
                'method' => PaymentMethod::PAY_AT_HOTEL,
            ]);

            if ($groupDiscountPolicy) {
                $this->groupDiscountRequestService->recordAutoTierApplied(
                    $booking, $groupDiscountPolicy, $roomCount, $total, $groupDiscount, Auth::id()
                );
            }

            return $booking->load(['bookingItems.roomType', 'payment', 'extraBedRequests']);
        });

        // Bắn SAU KHI transaction đã commit — cùng lý do với create() (khách tự
        // đặt): tránh listener (chạy qua queue) xử lý 1 booking có thể bị
        // rollback nếu có lỗi phát sinh sau đó trong cùng transaction.
        $pendingExtraBedRequest = $booking->pendingExtraBedRequest();
        if ($booking->status === BookingStatus::PENDING_CONSULTATION && $pendingExtraBedRequest) {
            event(new ExtraBedUnavailable($booking, $pendingExtraBedRequest));
        }

        return $booking;
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

        $booking = DB::transaction(function () use ($customer, $data, $roomTypes, $holdSessionId) {
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

            // Dòng nào cần giường phụ (vượt sức chứa gốc, đã qua
            // validateGuestCapacity() nên chắc chắn category hỗ trợ + khách
            // đã tick) — map index trong $lines => số giường phụ cần (tối đa
            // 1/phòng). Quyết định CẤP hay không dựa trên tồn kho thật diễn
            // ra SAU vòng lặp này (xem dưới), không phải ở đây.
            $extraBedByIndex = [];

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

                // Dùng đúng số CẦN THẬT SỰ (extraBedsNeeded(), khác nhau theo
                // category — xem doc-block hàm đó), không phải $quantity —
                // trước đây gán cứng $quantity khiến 1 dòng nhiều phòng nhưng
                // chỉ thiếu 1 giường vẫn bị cấp/trừ pool dư ra theo đúng số phòng.
                $extraBedCount = $this->extraBedsNeeded($roomType, $quantity, $adults, $children);
                if ($extraBedCount > 0) {
                    $extraBedByIndex[count($lines)] = $extraBedCount;
                }

                $lines[] = [
                    'room_type_id'        => $roomType->id,
                    'quantity'            => $quantity,
                    'adults'              => $adults,
                    'children'            => $children,
                    'infants'             => $infants,
                    'extra_beds'          => 0, // chỉ set thật sau khi biết tồn kho giường phụ, xem dưới
                    'extra_bed_surcharge' => 0, // idem — cộng vào $total dưới nếu được cấp
                    'price_per_night'     => $pricing['unit_price'],
                    'nights'              => $pricing['nights'],
                    'subtotal'            => $pricing['room_subtotal'],
                    'child_surcharge'     => $pricing['child_surcharge'],
                    'price_breakdown'     => $pricing['nightly_breakdown'],
                ];
            }

            // Giường phụ dùng chung 1 pool toàn khách sạn (hotel_info) — khóa
            // dòng cấu hình duy nhất đó để trừ pool an toàn khi nhiều đơn tạo
            // cùng lúc, cùng khuôn với việc khóa RoomType phía trên.
            $extraBedsNeeded    = array_sum($extraBedByIndex);
            $pendingConsultation = false;
            $extraBedAvailableSnapshot = 0;

            if ($extraBedsNeeded > 0) {
                HotelInfo::query()->lockForUpdate()->first();
                $extraBedAvailableSnapshot = $this->extraBedInventoryService->countAvailable($data['check_in'], $data['check_out']);

                if ($extraBedAvailableSnapshot >= $extraBedsNeeded) {
                    // Phụ thu giường phụ (hotel_info.extra_bed_surcharge_per_night ×
                    // số giường × số đêm) — tính qua PricingService để không lặp lại
                    // công thức, cùng cách child_surcharge đã làm.
                    foreach ($extraBedByIndex as $idx => $count) {
                        $roomType = $roomTypes[(int) $lines[$idx]['room_type_id']];
                        $extraBedPricing = $this->pricingService->calculate(
                            $roomType, $data['check_in'], $data['check_out'],
                            $lines[$idx]['quantity'], $lines[$idx]['children'], $count
                        );

                        $lines[$idx]['extra_beds']         = $count;
                        $lines[$idx]['extra_bed_surcharge'] = $extraBedPricing['extra_bed_surcharge'];
                        $total += $extraBedPricing['extra_bed_surcharge'];
                    }
                } else {
                    // Không đủ — đơn KHÔNG bị chặn, chuyển "chờ tư vấn" thay
                    // vì hết phòng thật sự (xem BookingStatus::PENDING_CONSULTATION,
                    // ExtraBedUnavailable event dispatch sau khi transaction
                    // này commit ở cuối method).
                    $pendingConsultation = true;
                }
            }

            $promotions = collect();
            $discount   = 0;
            $promoLines = [];

            if (! empty($data['promo_codes'])) {
                $promotions = $this->promotionService->findValidManyByCodes($data['promo_codes'], $customer);

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
                'national_id'     => $data['national_id'] ?? $customer->national_id,
                'note'            => $data['note'] ?? null,
                'total_amount'    => $total - $discount,
                'discount_amount' => $discount,
                'status'          => $pendingConsultation ? BookingStatus::PENDING_CONSULTATION : BookingStatus::PENDING_DEPOSIT,
                'deposit_expires_at' => $pendingConsultation ? null : now()->addMinutes(self::DEPOSIT_HOLD_MINUTES),
            ]);

            if ($pendingConsultation) {
                $this->logStatus($booking, null, BookingStatus::PENDING_CONSULTATION, $customer->id, "Giường phụ không đủ trong khoảng ngày đã chọn (cần {$extraBedsNeeded}, còn {$extraBedAvailableSnapshot}) — chờ khách/nhân viên chọn phương án.");
            } else {
                $this->logStatus($booking, null, BookingStatus::PENDING_DEPOSIT, $customer->id, 'Khách tạo đơn đặt phòng — chờ cọc 30% hoặc thanh toán đủ trong ' . self::DEPOSIT_HOLD_MINUTES . ' phút, quá hạn tự hủy nhả phòng.');
            }

            // Thông báo cho admin/staff về đơn mới (luôn gửi, bất kể có
            // pending_consultation hay không — ExtraBedUnavailableNotification
            // ở dưới là thông báo THỨ 2, riêng, chỉ báo phần thiếu giường phụ).
            User::whereIn('role', ['admin', 'staff'])->each(
                fn (User $u) => $u->notify(new NewBookingReceived($booking))
            );

            // Đơn KHÔNG còn tự động CONFIRMED ngay khi tạo — khách phải đặt
            // cọc 30% hoặc thanh toán đủ trong DEPOSIT_HOLD_MINUTES phút,
            // nếu không đơn tự hủy (xem cancelExpiredDepositBookings()).
            // Đơn chỉ thật sự CONFIRMED sau khi thanh toán thành công (xem
            // confirmAfterPayment()). Nhánh pending_consultation không có
            // đồng hồ đặt cọc — đơn chờ tư vấn cho tới khi được resolve.
            $customerMessage = $pendingConsultation
                ? "Đơn {$booking->booking_code} hiện đang chờ tư vấn — số giường phụ bạn yêu cầu tạm thời không đủ trong khoảng ngày này. Vui lòng chọn 1 phương án ở trang chi tiết đơn; khách sạn cũng đang được thông báo để hỗ trợ bạn."
                : "Đơn {$booking->booking_code} đã được tạo — vui lòng đặt cọc 30% hoặc thanh toán đủ trong " . self::DEPOSIT_HOLD_MINUTES . ' phút, nếu không đơn sẽ tự động hủy.';
            $customer->notify(new BookingStatusChanged($booking, $customerMessage));

            $createdItems = [];
            foreach ($lines as $idx => $line) {
                $createdItems[$idx] = $booking->bookingItems()->create($line);
            }

            if ($pendingConsultation) {
                $firstIndex = array_key_first($extraBedByIndex);

                ExtraBedRequest::create([
                    'booking_id'            => $booking->id,
                    'booking_item_id'       => $createdItems[$firstIndex]->id,
                    'requested_extra_beds'  => $extraBedsNeeded,
                    'available_extra_beds'  => $extraBedAvailableSnapshot,
                    'status'                => 'pending',
                ]);
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

            return $booking->load(['bookingItems.roomType', 'serviceItems.service', 'payment', 'extraBedRequests']);
        });

        // Bắn SAU KHI transaction đã commit — không dispatch bên trong
        // DB::transaction() ở trên để tránh listener (đặc biệt
        // NotifyStaffExtraBedUnavailable, chạy qua queue) xử lý 1 booking có
        // thể bị rollback nếu có lỗi phát sinh sau đó trong cùng transaction.
        $pendingExtraBedRequest = $booking->pendingExtraBedRequest();
        if ($booking->status === BookingStatus::PENDING_CONSULTATION && $pendingExtraBedRequest) {
            event(new ExtraBedUnavailable($booking, $pendingExtraBedRequest));
        }

        return $booking;
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
        $booking = Booking::with(['bookingItems.roomType.images', 'bookingItems.rooms', 'serviceItems.service', 'payment.statusLogs.changedBy', 'promotions', 'roomChangeRequests', 'earlyCheckinRequests', 'lateCheckoutRequests', 'incidentalInvoice.items', 'extraBedRequests'])
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
            throw ValidationException::withMessages([
                'status' => ['Không thể hủy đơn ở trạng thái hiện tại.'],
            ]);
        }

        // Phí hủy theo bậc (Booking::cancellationFeePercent()) chỉ có ý nghĩa
        // nếu khách đã thực sự nộp (cọc hoặc thanh toán) — đơn đang
        // pending_deposit CHƯA làm gì cả (payment vẫn UNPAID) cũng nằm trong
        // canCancelByCustomer() từ giờ, không có gì để giữ lại (dùng
        // payment->status thay vì amount_collected vì payDepositDemo() không
        // ghi amount_collected cho phần cọc).
        $hasPaidAnything = $booking->payment && $booking->payment->status !== PaymentStatus::UNPAID;
        $feePercent = $hasPaidAnything ? $booking->cancellationFeePercent() : 0;

        $oldStatus = $booking->status;
        $booking->update(['status' => BookingStatus::CANCELLED]);
        $note = $feePercent > 0
            ? 'Khách hủy đơn — còn ' . round($booking->hoursUntilCheckIn(), 1) . " giờ tới giờ nhận phòng, phí hủy {$feePercent}% tổng tiền đơn."
            : 'Khách hủy đơn.';
        $this->logStatus($booking, $oldStatus, BookingStatus::CANCELLED, $customer->id, $note);

        $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã được hủy." . ($feePercent > 0 ? " Phí hủy {$feePercent}% (theo chính sách hủy), phần còn lại sẽ được hoàn." : '')));

        $refundOk = $this->attemptRefund($booking, $customer->id, $feePercent);

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

        $this->processBookingExpiry($booking->id);
        $booking->refresh();

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
        [$txnRef, $outstanding, $vnpaySessionExpiresAt] = DB::transaction(function () use ($booking, $customer) {
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

            // Mốc hết hạn phiên VNPay = mốc SỚM HƠN giữa "tối đa txn_expire_minutes
            // kể từ bây giờ" và "hạn giữ chỗ hiện tại của booking" — KHÔNG bao giờ
            // cấp 1 cửa sổ mới đầy đủ tính từ lúc bấm nếu hold sắp hết, để đồng
            // hồ đếm ngược VNPay khách thấy CHÍNH LÀ phần còn lại của đồng hồ giữ
            // chỗ (liên tục, không "reset" lại từ đầu mỗi lần bấm) — đúng cảm
            // nhận người dùng mong đợi, đồng thời tự động đảm bảo phiên VNPay
            // không bao giờ dài hơn hold (canMarkPaymentAsPaid() ở trên đã chặn
            // hẳn nếu hold đã hết hạn, nên deposit_expires_at ở đây luôn ở tương
            // lai). Lớp bảo vệ an toàn cho IPN tới trễ SAU mốc này nằm ở
            // BookingStatus::EXPIRED_PENDING_CHECK (processBookingExpiry()), không
            // còn cần "nới hold ra" như trước.
            $maxWindowEnd = now()->addMinutes((int) config('services.vnpay.txn_expire_minutes', 15));
            $vnpaySessionExpiresAt = ($booking->deposit_expires_at && $booking->deposit_expires_at->lt($maxWindowEnd))
                ? $booking->deposit_expires_at
                : $maxWindowEnd;

            if (! $reuseExisting) {
                $payment->update([
                    'method'                    => PaymentMethod::ONLINE_VNPAY,
                    'status'                    => PaymentStatus::PENDING,
                    'transaction_code'          => $txnRef,
                    'pending_gateway_amount'    => $outstanding,
                    'vnpay_session_expires_at'  => $vnpaySessionExpiresAt,
                ]);

                if ($oldStatus !== PaymentStatus::PENDING) {
                    $this->logPaymentStatus($payment, $oldStatus, PaymentStatus::PENDING, $customer->id, 'Khách chuyển sang cổng VNPay để thanh toán.');
                }
            } else {
                $payment->update(['vnpay_session_expires_at' => $vnpaySessionExpiresAt]);
            }

            return [$txnRef, $outstanding, $vnpaySessionExpiresAt];
        });

        // route() dùng root URL của request HIỆN TẠI (không phải APP_URL tĩnh
        // trong .env) — khách vào bằng host nào (localhost, 127.0.0.1, LAN
        // IP...) thì VNPay sẽ redirect về đúng host đó, tránh mất session
        // cookie (cookie theo host) khi quay lại từ VNPay và bị đá ra trang
        // đăng nhập dù thanh toán đã thành công (IPN vẫn ghi nhận đúng, chỉ
        // riêng lượt redirect trình duyệt bị lệch host).
        $paymentUrl = $this->vnPayService->buildPaymentUrl(
            $txnRef,
            $outstanding,
            'Thanh toan booking ' . $booking->booking_code,
            $ipAddress,
            route('payment.vnpay.return'),
            $vnpaySessionExpiresAt,
        );

        return ['booking' => $booking, 'payment_url' => $paymentUrl];
    }

    /**
     * Khách đã bấm "Thanh toán qua VNPay" (payment chuyển sang PENDING +
     * method ONLINE_VNPAY ở initiateVnpayPayment()) nhưng đổi ý muốn quay lại
     * chọn hình thức khác (đặt cọc 30%, chuyển khoản QR) thay vì tiếp tục
     * VNPay — hạ payment về UNPAID để canPayDeposit()/canMarkPaymentAsPaid()
     * cho phép chọn lại đầy đủ các hình thức.
     *
     * KHÔNG xóa transaction_code/pending_gateway_amount: phiên VNPay cũ vẫn
     * có thể được khách hoàn tất song song (tab khác) hoặc IPN đến trễ —
     * confirmVnpayReturn() cố tình không dựa vào payment->status === PENDING
     * để xác nhận (xem comment ở đầu hàm đó), nên nếu giao dịch cũ thật sự
     * thành công sau khi đã "hủy" ở đây, hệ thống vẫn ghi nhận đúng thay vì
     * mất tiền khách.
     */
    public function cancelVnpayAttempt(int $bookingId, User $customer): Booking
    {
        $booking = $this->findForCustomer($bookingId, $customer);

        DB::transaction(function () use ($booking, $customer) {
            $payment = Payment::whereKey($booking->payment->id)->lockForUpdate()->first();

            if ($payment->status !== PaymentStatus::PENDING || $payment->method !== PaymentMethod::ONLINE_VNPAY) {
                throw ValidationException::withMessages([
                    'status' => ['Không có giao dịch VNPay đang chờ để hủy.'],
                ]);
            }

            $payment->update(['status' => PaymentStatus::UNPAID]);
            $this->logPaymentStatus($payment, PaymentStatus::PENDING, PaymentStatus::UNPAID, $customer->id, 'Khách hủy phiên VNPay để chọn hình thức thanh toán khác.');
        });

        return $booking->fresh('payment');
    }

    /**
     * Xử lý phản hồi từ VNPay (dùng chung cho cả return URL và IPN — hai nơi
     * gọi cùng logic idempotent này, chỉ khác định dạng response trả về cho
     * người gọi). Xác thực chữ ký trước khi tin bất kỳ trường nào trong
     * $query, tránh giả mạo kết quả thanh toán.
     *
     * QUAN TRỌNG: với callback báo THÀNH CÔNG, cổng idempotency KHÔNG được
     * dựa vào "payment->status === PENDING" — payment có thể đã bị
     * processBookingExpiry() (job quét quá hạn) chuyển sang UNPAID trong lúc
     * khách vẫn đang thanh toán thật trên VNPay (race hold-expiry vs IPN).
     * Nếu chỉ tin payment->status để quyết định "đã xử lý, bỏ qua", một giao
     * dịch báo thành công thật sẽ bị lặng lẽ nuốt mất (tiền bị trừ nhưng hệ
     * thống coi như không có gì xảy ra) — đây chính là lỗi mất tiền khách đã
     * phát hiện. Quy tắc mới: LUÔN kiểm tra khoản thành công, chỉ quyết định
     * "xác nhận bình thường" hay "tạo yêu cầu hoàn tiền" dựa vào việc phòng
     * ĐÃ được nhả hay chưa (booking->status === CANCELLED hay không) — vì
     * BookingStatus::holdingStatuses() (bao gồm cả EXPIRED_PENDING_CHECK
     * trong lúc đệm) đảm bảo phòng chưa từng được nhả cho ai khác trước khi
     * cancelled thật sự.
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

        // Nếu rơi vào nhánh "orphan" (booking đã cancelled, cần hoàn tiền),
        // transaction bên dưới chỉ tạo bản ghi RefundRequest (PENDING) — việc
        // GỌI API hoàn tiền thật (HTTP ra ngoài) + gửi thông báo phải làm SAU
        // khi transaction đã commit, không giữ lock DB trong lúc chờ network
        // (cùng nguyên tắc với attemptRefund() ở cancelByAdmin()).
        $refundRequestToResolve = null;

        // VNPay có thể gọi IPN (server-to-server) gần như đồng thời với lúc
        // khách được redirect về return URL, hoặc tự động thử lại IPN nếu
        // lần gọi trước timeout — cả hai nơi đều gọi hàm này cho CÙNG 1 giao
        // dịch. Khóa dòng payment trong transaction + đọc lại status SAU khi
        // đã khóa, tránh 2 lời gọi đồng thời cùng đọc thấy PENDING trước khi
        // bên kia commit, dẫn tới cộng amount_collected/gửi thông báo 2 lần
        // cho 1 giao dịch thật.
        $result = DB::transaction(function () use ($payment, $booking, $query, &$refundRequestToResolve) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            $isSuccess = $this->vnPayService->isSuccessResponse($query);

            // Giao dịch này đã được xác nhận PAID trước đó (khách bấm lại
            // nút back/refresh trên trang return, hoặc IPN gọi lại trùng) —
            // idempotent, không xử lý lại.
            if ($payment->status === PaymentStatus::PAID) {
                return ['booking' => $booking, 'success' => true, 'code' => 'already_confirmed', 'message' => 'Đơn đã được thanh toán trước đó.'];
            }

            if (! $isSuccess) {
                // Callback báo lỗi/hủy chỉ còn ý nghĩa nếu giao dịch đang THẬT
                // SỰ dở dang (PENDING) — nếu payment đã ở trạng thái cuối khác
                // (VD UNPAID do đã bị tự hủy quá hạn từ trước), đây là callback
                // trễ/trùng lặp không còn liên quan, không được hạ cấp lại.
                if ($payment->status !== PaymentStatus::PENDING) {
                    return ['booking' => $booking, 'success' => false, 'code' => 'already_confirmed', 'message' => 'Giao dịch đã được xử lý trước đó.'];
                }

                $oldStatus = $payment->status;
                $responseCode = $query['vnp_ResponseCode'] ?? 'unknown';

                $payment->update([
                    'status'                 => PaymentStatus::UNPAID,
                    'pending_gateway_amount' => null,
                    'note'                   => 'VNPay báo lỗi/hủy giao dịch, mã phản hồi: ' . $responseCode,
                ]);
                $this->logPaymentStatus($payment, $oldStatus, PaymentStatus::UNPAID, $booking->user_id, "Thanh toán VNPay thất bại/bị hủy (mã {$responseCode}).");

                return ['booking' => $booking->fresh('payment'), 'success' => false, 'code' => 'ok', 'message' => 'Thanh toán VNPay không thành công.'];
            }

            // Số tiền THẬT SỰ đã yêu cầu VNPay thu ở lần redirect này — so khớp
            // với vnp_Amount VNPay trả về, tránh tin nhầm 1 callback báo đúng
            // mã giao dịch nhưng sai số tiền (VD do payment.amount bị đổi giữa
            // lúc redirect và lúc VNPay gọi về, hoặc callback cũ/giả bị phát lại).
            $expectedAmount = (int) round((float) ($payment->pending_gateway_amount ?? $payment->amount) * 100);
            $callbackAmount = (int) ($query['vnp_Amount'] ?? -1);

            if ($expectedAmount !== $callbackAmount) {
                $this->logPaymentStatus($payment, $payment->status, $payment->status, $booking->user_id, "VNPay báo thành công nhưng số tiền không khớp (mong đợi {$expectedAmount}, nhận {$callbackAmount}) — từ chối xác nhận, cần kiểm tra thủ công.");

                return ['booking' => $booking, 'success' => false, 'code' => 'amount_mismatch', 'message' => 'Số tiền xác nhận từ VNPay không khớp, vui lòng liên hệ khách sạn.'];
            }

            // vnp_PayDate đến từ VNPay theo định dạng YmdHis (vd "20260717141518")
            // — cột gateway_paid_at cast 'datetime' nên phải parse đúng format
            // trước khi gán, nếu không Carbon sẽ đoán sai định dạng chuỗi số này.
            $gatewayPaidAt = isset($query['vnp_PayDate'])
                ? \Carbon\Carbon::createFromFormat('YmdHis', $query['vnp_PayDate'])
                : now();

            $thisTxnAmount = (float) ($payment->pending_gateway_amount ?? $payment->amount);

            // Khóa dòng booking trước khi quyết định — đây là điểm mấu chốt
            // chống race với processBookingExpiry() (job quét quá hạn/gọi rải
            // rác ở các điểm thanh toán khác): 2 giao dịch cùng lockForUpdate()
            // trên CÙNG 1 dòng booking sẽ tự serialize, bên thắng lock trước
            // quyết định số phận cuối cùng, bên còn lại đọc lại trạng thái MỚI
            // NHẤT sau khi bên kia commit rồi mới quyết định tiếp.
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->first();

            $oldStatus = $payment->status;

            if ($lockedBooking->status !== BookingStatus::CANCELLED) {
                // An toàn xác nhận — phòng CHƯA từng được nhả cho ai khác, bất
                // kể booking đang ở trạng thái non-cancelled nào (pending_deposit
                // dù deposit_expires_at đã qua nhưng job chưa kịp xử lý,
                // expired_pending_check đang trong khoảng đệm, confirmed,
                // checked_in...). confirmAfterPayment() tự no-op nếu booking
                // không còn ở pending_deposit.
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

                $this->confirmAfterPayment($lockedBooking, $booking->user_id);

                $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã thanh toán thành công qua VNPay."));

                return ['booking' => $booking->fresh('payment'), 'success' => true, 'code' => 'ok', 'message' => 'Thanh toán VNPay thành công.'];
            }

            // Booking đã CANCELLED hẳn (hết hạn giữ chỗ + khoảng đệm, hoặc
            // admin hủy tay) trước khi callback này tới — phòng có thể đã
            // được nhả/bán cho khách khác. Tiền đã bị VNPay trừ thật, KHÔNG
            // được lặng lẽ bỏ qua: ghi nhận vào Payment để có dấu vết đối
            // soát (không chuyển PAID vì booking không còn hiệu lực), tạo
            // RefundRequest (idempotent theo payment_id — unique ở DB) để xử
            // lý hoàn tiền, dừng lại ở đây trong transaction (không gọi API
            // hoàn tiền/HTTP ở đây).
            $existingRefundRequest = RefundRequest::where('payment_id', $payment->id)->first();

            if ($existingRefundRequest) {
                return ['booking' => $lockedBooking, 'success' => false, 'code' => 'refund_pending', 'message' => 'Đơn đã bị hủy trước khi thanh toán được xác nhận — yêu cầu hoàn tiền đang được xử lý.'];
            }

            $payment->update([
                'gateway_transaction_no' => $query['vnp_TransactionNo'] ?? null,
                'gateway_paid_at'        => $gatewayPaidAt,
                'last_gateway_amount'    => $thisTxnAmount,
                'amount_collected'       => (float) $payment->amount_collected + $thisTxnAmount,
                'pending_gateway_amount' => null,
                'note'                   => 'VNPay báo thanh toán thành công SAU KHI đơn đã bị hủy do quá hạn giữ chỗ — tiền đã bị trừ, đã tạo yêu cầu hoàn tiền.',
            ]);
            $this->logPaymentStatus($payment, $oldStatus, $oldStatus, $booking->user_id, 'VNPay báo thanh toán thành công nhưng đơn đã bị hủy trước đó — tạo yêu cầu hoàn tiền, chờ xử lý.');

            $refundRequestToResolve = RefundRequest::create([
                'booking_id'             => $lockedBooking->id,
                'payment_id'             => $payment->id,
                'transaction_code'       => $payment->transaction_code,
                'gateway_transaction_no' => $query['vnp_TransactionNo'] ?? null,
                'amount'                 => $thisTxnAmount,
                'reason'                 => 'Booking đã tự động hủy do quá hạn giữ chỗ (kể cả thời gian đệm) trước khi VNPay xác nhận thanh toán thành công.',
                'status'                 => RefundRequestStatus::PENDING,
            ]);

            Log::warning('Phát hiện thanh toán VNPay trễ trên booking đã hủy — đã tạo refund request.', [
                'booking_id'        => $lockedBooking->id,
                'booking_code'      => $lockedBooking->booking_code,
                'payment_id'        => $payment->id,
                'transaction_code'  => $payment->transaction_code,
                'amount'            => $thisTxnAmount,
                'refund_request_id' => $refundRequestToResolve->id,
            ]);

            return ['booking' => $lockedBooking, 'success' => false, 'code' => 'refund_pending', 'message' => 'Đơn đã bị hủy trước khi ghi nhận thanh toán — hệ thống đã tạo yêu cầu hoàn tiền, chúng tôi sẽ liên hệ sớm.'];
        });

        if ($refundRequestToResolve) {
            $this->resolveOrphanedRefund($refundRequestToResolve);
        }

        return $result;
    }

    /**
     * Xử lý 1 RefundRequest vừa được tạo trong confirmVnpayReturn() — gọi
     * NGOÀI transaction DB (đây là lời gọi HTTP ra ngoài, không được giữ lock
     * booking/payment trong lúc chờ network, cùng nguyên tắc với
     * attemptRefund()). Luôn thử hoàn tiền tự động qua API VNPay (đã tích
     * hợp sẵn — xem VNPayService::refund()), NHƯNG vẫn luôn gửi thông báo
     * cho khách + admin/staff dù tự động hoàn có thành công hay không, vì
     * đây là tình huống bất thường liên quan tới tiền, cần con người xác
     * nhận lại chứ không chỉ tin máy.
     */
    private function resolveOrphanedRefund(RefundRequest $refundRequest): void
    {
        $payment = $refundRequest->payment;
        $booking = $refundRequest->booking;

        $refundOk = false;
        $response = null;

        try {
            $response = $this->vnPayService->refund(
                (string) $refundRequest->transaction_code,
                (float) $refundRequest->amount,
                (string) $refundRequest->gateway_transaction_no,
                $payment->gateway_paid_at?->format('YmdHis') ?? now()->format('YmdHis'),
                'Hoan tien tu dong - don da huy ' . $booking->booking_code,
                'system',
                '127.0.0.1',
            );
            $refundOk = ($response['vnp_ResponseCode'] ?? null) === '00';
        } catch (\Throwable $e) {
            Log::error('Gọi API hoàn tiền VNPay tự động thất bại cho refund request #' . $refundRequest->id, ['error' => $e->getMessage()]);
            $response = ['error' => $e->getMessage()];
        }

        $refundRequest->update([
            'status'           => $refundOk ? RefundRequestStatus::REFUNDED : RefundRequestStatus::FAILED,
            'gateway_response' => $response,
            'resolved_at'      => $refundOk ? now() : null,
        ]);

        // Chỉ hạ payment về REFUNDED nếu trạng thái hiện tại chưa phải PAID —
        // nếu payment này đã có 1 chu kỳ thu tiền HỢP LỆ khác trước đó (VD
        // booking confirmed rồi mới bị admin hủy trong lúc 1 khoản phụ phí
        // đang PENDING qua VNPay), khoản PAID cũ không liên quan gì tới orphan
        // refund này, không được ghi đè.
        if ($refundOk && $payment->status !== PaymentStatus::PAID) {
            $oldStatus = $payment->status;
            $payment->update([
                'amount_collected' => max(0, (float) $payment->amount_collected - (float) $refundRequest->amount),
                'status'           => PaymentStatus::REFUNDED,
            ]);
            $this->logPaymentStatus($payment, $oldStatus, PaymentStatus::REFUNDED, null, 'Tự động hoàn tiền qua API VNPay sau khi phát hiện thanh toán trễ trên đơn đã hủy (refund request #' . $refundRequest->id . ').');
        }

        $booking->user?->notify(new OrphanedPaymentNeedsRefund($booking, $refundRequest));
        User::whereIn('role', ['admin', 'staff'])->each(
            fn (User $u) => $u->notify(new OrphanedPaymentNeedsRefund($booking, $refundRequest))
        );
    }

    /**
     * Khách tự báo đã chuyển khoản — chuyển thanh toán sang "đang xử lý" chờ
     * admin/staff đối soát và xác nhận thủ công (không tự động sang paid).
     *
     * Cho phép cả từ PENDING_DEPOSIT/PENDING (trước đây chỉ CONFIRMED) vì
     * trang khách hàng giờ hiện QR chuyển khoản 100% ngay từ lúc giữ chỗ.
     * Riêng PENDING_DEPOSIT/EXPIRED_PENDING_CHECK cần khóa dòng + đưa đơn ra
     * khỏi diện tự hủy theo giờ (xóa deposit_expires_at/expired_pending_check_at,
     * chuyển sang PENDING) NGAY khi khách báo đã chuyển khoản — nếu không,
     * job cancelExpiredDepositBookings() (hoặc processBookingExpiry() gọi
     * rải rác ở nơi khác) có thể hủy đơn + nhả phòng ngay sau đó dù tiền đã
     * chuyển, chỉ vì nhân viên chưa kịp đối soát trong lúc hạn giữ chỗ (kể cả
     * khoảng đệm) còn lại quá ngắn.
     */
    public function markBankTransferPending(int $bookingId, User $customer): Booking
    {
        $booking = $this->findForCustomer($bookingId, $customer);

        if (in_array($booking->status, [BookingStatus::PENDING_DEPOSIT, BookingStatus::EXPIRED_PENDING_CHECK], true)) {
            $this->processBookingExpiry($booking->id);
            $booking->refresh();
        }

        $canReportTransfer = in_array($booking->status, [BookingStatus::PENDING_DEPOSIT, BookingStatus::EXPIRED_PENDING_CHECK, BookingStatus::PENDING, BookingStatus::CONFIRMED], true)
            && $booking->payment
            && $booking->payment->status->canTransitionTo(PaymentStatus::PENDING);

        if (! $canReportTransfer) {
            throw ValidationException::withMessages([
                'status' => [$booking->status === BookingStatus::CANCELLED
                    ? 'Đơn đã quá hạn giữ chỗ và tự động bị hủy, vui lòng đặt phòng lại.'
                    : 'Chỉ có thể báo chuyển khoản khi đơn chưa bị hủy và chưa thanh toán.'],
            ]);
        }

        return DB::transaction(function () use ($bookingId, $customer) {
            $booking = Booking::with('payment')->whereKey($bookingId)->lockForUpdate()->first();

            if ($booking->status === BookingStatus::CANCELLED) {
                throw ValidationException::withMessages([
                    'status' => ['Đơn đã quá hạn giữ chỗ và tự động bị hủy, vui lòng đặt phòng lại.'],
                ]);
            }

            if (in_array($booking->status, [BookingStatus::PENDING_DEPOSIT, BookingStatus::EXPIRED_PENDING_CHECK], true)) {
                $oldBookingStatus = $booking->status;
                $booking->update(['status' => BookingStatus::PENDING, 'deposit_expires_at' => null, 'expired_pending_check_at' => null]);
                $this->logStatus($booking, $oldBookingStatus, BookingStatus::PENDING, $customer->id, 'Khách báo đã chuyển khoản 100% qua QR — đưa đơn khỏi diện giữ chỗ có hạn, chờ khách sạn đối soát.');
            }

            $oldStatus = $booking->payment->status;
            $booking->payment->update([
                'method' => PaymentMethod::BANK_TRANSFER,
                'status' => PaymentStatus::PENDING,
            ]);
            $this->logPaymentStatus($booking->payment, $oldStatus, PaymentStatus::PENDING, $customer->id, 'Khách báo đã chuyển khoản, chờ xác nhận.');

            return $booking->fresh('payment');
        });
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

        $this->processBookingExpiry($booking->id);
        $booking->refresh();

        if (! $booking->canPayDeposit()) {
            throw ValidationException::withMessages([
                'status' => [$booking->status === BookingStatus::CANCELLED
                    ? 'Đơn đã quá hạn giữ chỗ (' . self::DEPOSIT_HOLD_MINUTES . ' phút) và tự động bị hủy, vui lòng đặt phòng lại.'
                    : 'Chỉ có thể đặt cọc khi đơn đã được xác nhận và chưa thanh toán.'],
            ]);
        }

        // Khóa dòng booking trong lúc xác nhận cọc — tránh đụng độ với
        // command tự hủy đơn quá hạn (cancelExpiredDepositBookings()) chạy
        // đúng lúc khách vừa bấm cọc: bên nào khóa được trước sẽ thắng, bên
        // sau đọc lại trạng thái mới nhất sau khi bên kia commit.
        DB::transaction(function () use ($booking, $customer) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->first();

            if ($lockedBooking->status === BookingStatus::CANCELLED) {
                throw ValidationException::withMessages([
                    'status' => ['Đơn đã quá hạn giữ chỗ và tự động bị hủy, vui lòng đặt phòng lại.'],
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

            $this->confirmAfterPayment($lockedBooking, $customer->id);
        });

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
        return Booking::with(['user', 'promotions', 'bookingItems.roomType', 'bookingItems.rooms', 'bookingItems.bookingItemRooms.room', 'serviceItems.service', 'payment.statusLogs.changedBy', 'statusLogs.changedBy', 'auditLogs.user', 'earlyCheckinRequests', 'lateCheckoutRequests', 'incidentalInvoice.items', 'extraBedRequests'])
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

        if ($newStatus === PaymentStatus::PAID && ! in_array($booking->status, [BookingStatus::PENDING_DEPOSIT, BookingStatus::EXPIRED_PENDING_CHECK, BookingStatus::CONFIRMED, BookingStatus::CHECKED_IN], true)) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể đánh dấu đã thanh toán khi đơn ở trạng thái chờ cọc/thanh toán, đã xác nhận hoặc đã check-in.'],
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

        // Admin/staff xác nhận cọc/thanh toán thủ công cho đơn đang chờ
        // (vd đơn tạo hộ qua điện thoại, khách chuyển khoản trực tiếp) —
        // cũng phải xác nhận đơn giống như khi khách tự thanh toán online.
        if (in_array($newStatus, [PaymentStatus::DEPOSIT_PAID, PaymentStatus::PAID], true)) {
            $this->confirmAfterPayment($booking, Auth::id());
        }

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
     * phòng. Ghi vào "hóa đơn phát sinh" riêng (IncidentalInvoiceService) —
     * KHÔNG đụng tới booking.total_amount/payment (tiền phòng gốc) nữa, chỉ
     * thu 1 lần lúc trả phòng (xem checkOut()).
     */
    public function addServiceItem(Booking $booking, int $serviceId, int $quantity, ?float $amount = null, ?string $note = null): Booking
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

        // price = null nghĩa là dịch vụ chưa có bảng giá cố định (VD: thuê xe
        // theo đoàn, trang trí theo yêu cầu...) — bắt buộc nhân viên tự nhập
        // amount, khác với dịch vụ có giá niêm yết (luôn dùng service->price,
        // bỏ qua amount gửi lên để tránh nhân viên tự ý sửa giá catalog).
        if ($service->price !== null) {
            $subtotal = (float) $service->price * $quantity;
        } else {
            if ($amount === null || $amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ["Dịch vụ \"{$service->name}\" chưa có giá cố định, vui lòng nhập số tiền."],
                ]);
            }
            $subtotal = $amount;
        }

        $description = $note ? "{$service->name} × {$quantity} — {$note}" : "{$service->name} × {$quantity}";

        return DB::transaction(function () use ($booking, $service, $quantity, $subtotal, $description) {
            $serviceItem = $booking->serviceItems()->create([
                'service_id' => $service->id,
                'quantity'   => $quantity,
                'unit_price' => $subtotal / $quantity,
                'subtotal'   => $subtotal,
            ]);

            $this->incidentalInvoiceService->addItem(
                $booking, 'service', $description, $subtotal, $serviceItem->id
            );

            return $booking->fresh(['serviceItems.service', 'payment', 'incidentalInvoice.items']);
        });
    }

    /**
     * Admin/staff ghi nhận phụ phí phát sinh tùy ý (hư hỏng, nhận phòng sớm
     * tự động, trả phòng muộn tự động...) không gắn với dịch vụ nào trong
     * catalog — khác addServiceItem() ở chỗ đây là số tiền + lý do nhập tay.
     * Ghi thẳng vào "hóa đơn phát sinh" riêng (IncidentalInvoiceService) —
     * KHÔNG đụng payment (tiền phòng gốc) nữa, xem checkOut().
     */
    public function addSurcharge(Booking $booking, float $amount, string $note, ?int $surchargeItemId = null, int $quantity = 1): Booking
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

        return DB::transaction(function () use ($booking, $amount, $note, $surchargeItemId, $quantity) {
            $this->incidentalInvoiceService->addItem($booking, 'surcharge', $note, $amount, null, $surchargeItemId, $quantity);

            return $booking->fresh(['payment', 'incidentalInvoice.items']);
        });
    }

    /**
     * Xem trước (không ghi DB) chi phí + tình trạng phòng nếu gia hạn đơn
     * đang lưu trú tới ngày trả phòng mới — dùng cho preview real-time (JS
     * fetch khi lễ tân đổi ngày) VÀ làm bước tính toán dùng chung bên trong
     * extendStay(). Xem computeExtension() để biết chi tiết validate.
     *
     * $switchRoomTypeId/$switchRoomId: lễ tân đã chọn 1 phương án đổi loại
     * phòng + phòng vật lý cụ thể từ danh sách `alternatives` trả về ở lần
     * preview trước (khi loại phòng hiện tại hết chỗ) — preview lại để chốt
     * giá chính xác cho đúng phòng đó trước khi xác nhận thật.
     *
     * @return array{needs_switch: bool, nights_added: int, extra_amount?: float, new_check_out: string, alternatives?: array, switch?: array}
     */
    public function previewExtendStay(Booking $booking, string $newCheckOut, ?int $switchRoomTypeId = null, ?int $switchRoomId = null): array
    {
        $extension = $this->computeExtension($booking, $newCheckOut, $switchRoomTypeId, $switchRoomId);

        if ($extension['needs_switch']) {
            return [
                'needs_switch'  => true,
                'alternatives'  => $extension['alternatives'],
                'nights_added'  => $extension['nights_added'],
                'new_check_out' => $newCheckOut,
            ];
        }

        return [
            'needs_switch'        => false,
            'nights_added'        => $extension['nights_added'],
            'extra_amount'        => $extension['extra_amount'],
            'new_check_out'       => $newCheckOut,
            'rooms'               => collect($extension['items'] ?? [])->pluck('room_number')->filter()->values(),
            'switch_alternatives' => $extension['switch_alternatives'] ?? [],
            'switch'              => isset($extension['switch']) ? [
                'room_type_id'   => $extension['switch']['room_type']->id,
                'room_type_name' => $extension['switch']['room_type']->name,
                'room_id'        => $extension['switch']['room']->id,
                'room_number'    => $extension['switch']['room']->room_number,
            ] : null,
        ];
    }

    /**
     * Lễ tân/admin gia hạn thời gian thuê phòng cho đơn ĐANG lưu trú
     * (checked_in) — khách muốn ở thêm đêm, xử lý trực tiếp tại quầy (không
     * qua luồng gửi yêu cầu/duyệt như RoomChangeRequest/LateCheckoutRequest,
     * vì đây là khách đang có mặt tại khách sạn).
     *
     * Số đêm thêm ghi vào "hóa đơn phát sinh" riêng (IncidentalInvoiceService)
     * — giống addServiceItem()/addSurcharge(), KHÔNG đụng booking.total_amount/
     * payment (tiền phòng gốc), chỉ thu 1 lần lúc checkOut().
     *
     * $switchRoomTypeId/$switchRoomId: khi loại phòng hiện tại hết chỗ cho
     * những đêm gia hạn, lễ tân chọn 1 phương án đổi loại phòng (từ
     * `alternatives` do previewExtendStay() trả về) + 1 phòng vật lý cụ thể
     * còn trống của loại đó. Vì 1 BookingItem chỉ mang 1 room_type_id cho
     * toàn bộ `nights` của nó (không thể "vá" loại phòng khác vào giữa),
     * phần đêm gia hạn được tách thành 1 BookingItem MỚI ở loại phòng mới;
     * item gốc giữ nguyên số đêm/giá ban đầu.
     *
     * @return array{booking: Booking, nights_added: int, extra_amount: float, switched: bool}
     */
    public function extendStay(Booking $booking, string $newCheckOut, ?int $switchRoomTypeId = null, ?int $switchRoomId = null): array
    {
        $extension = $this->computeExtension($booking, $newCheckOut, $switchRoomTypeId, $switchRoomId);

        if ($extension['needs_switch']) {
            throw ValidationException::withMessages([
                'new_check_out' => ['Loại phòng hiện tại đã hết chỗ cho khoảng ngày này, vui lòng chọn 1 phương án đổi phòng trước khi xác nhận.'],
            ]);
        }

        return DB::transaction(function () use ($booking, $newCheckOut, $extension) {
            if (isset($extension['switch'])) {
                $switch  = $extension['switch'];
                $oldItem = $switch['old_item'];

                $newItem = BookingItem::create([
                    'booking_id'          => $booking->id,
                    'room_type_id'        => $switch['room_type']->id,
                    'quantity'            => 1,
                    'adults'              => $oldItem->adults,
                    'children'            => $oldItem->children,
                    'infants'             => $oldItem->infants,
                    'extra_beds'          => $switch['extra_beds_needed'] ?? 0,
                    'price_per_night'     => $switch['pricing']['unit_price'],
                    'nights'              => $switch['pricing']['nights'],
                    'subtotal'            => $switch['pricing']['room_subtotal'],
                    'child_surcharge'     => $switch['pricing']['child_surcharge'],
                    'extra_bed_surcharge' => $switch['pricing']['extra_bed_surcharge'],
                    'price_breakdown'     => $switch['pricing']['nightly_breakdown'],
                ]);

                $oldRoomAssignment = $oldItem->bookingItemRooms()->whereNull('checked_out_at')->first();
                if ($oldRoomAssignment) {
                    $oldRoomAssignment->update(['checked_out_at' => now()]);
                    $oldRoomAssignment->room->update(['housekeeping_status' => 'dirty']);
                }

                BookingItemRoom::create([
                    'booking_item_id' => $newItem->id,
                    'room_id'         => $switch['room']->id,
                    'checked_in_at'   => now(),
                ]);

                $oldRoomNumber = $oldRoomAssignment?->room?->room_number ?? $oldItem->roomType->name;
                $this->incidentalInvoiceService->addItem(
                    $booking,
                    'surcharge',
                    "Gia hạn thêm {$extension['nights_added']} đêm, đổi từ phòng \"{$oldRoomNumber}\" sang phòng \"{$switch['room']->room_number}\" ({$switch['room_type']->name}) tới " . \Carbon\Carbon::parse($newCheckOut)->format('d/m/Y'),
                    $switch['pricing']['total_price']
                );
            } else {
                foreach ($extension['items'] as $line) {
                    $line['item']->update([
                        'nights'              => $line['item']->nights + $extension['nights_added'],
                        'subtotal'            => $line['item']->subtotal + $line['pricing']['room_subtotal'],
                        'child_surcharge'     => $line['item']->child_surcharge + $line['pricing']['child_surcharge'],
                        'extra_bed_surcharge' => $line['item']->extra_bed_surcharge + $line['pricing']['extra_bed_surcharge'],
                        'price_breakdown'     => array_merge($line['item']->price_breakdown ?? [], $line['pricing']['nightly_breakdown']),
                    ]);

                    $this->incidentalInvoiceService->addItem(
                        $booking,
                        'surcharge',
                        "Gia hạn thêm {$extension['nights_added']} đêm phòng {$line['item']->roomType->name} (đến " . \Carbon\Carbon::parse($newCheckOut)->format('d/m/Y') . ')',
                        $line['pricing']['total_price']
                    );
                }
            }

            $booking->update([
                'check_out' => $newCheckOut,
                'nights'    => $booking->nights + $extension['nights_added'],
            ]);

            $amountText = number_format($extension['extra_amount'], 0, ',', '.') . 'đ';
            $switchNote = isset($extension['switch']) ? " (đã chuyển sang phòng {$extension['switch']['room']->room_number})" : '';
            $booking->user?->notify(new BookingStatusChanged(
                $booking,
                "Đơn {$booking->booking_code} đã được gia hạn thêm {$extension['nights_added']} đêm{$switchNote}, tới ngày " . \Carbon\Carbon::parse($newCheckOut)->format('d/m/Y') . ". Phí phát sinh {$amountText} đã ghi vào hóa đơn phát sinh, thanh toán khi trả phòng."
            ));

            return [
                'booking'      => $booking->fresh(['bookingItems.roomType', 'incidentalInvoice.items']),
                'nights_added' => $extension['nights_added'],
                'extra_amount' => $extension['extra_amount'],
                'switched'     => isset($extension['switch']),
            ];
        });
    }

    /**
     * Validate + tính toán chung cho previewExtendStay()/extendStay() —
     * KHÔNG ghi DB, chỉ đọc + tính. Tách riêng để 2 hàm public không lặp lại
     * cùng logic validate/pricing (preview JSON và hành động thật phải luôn
     * tính ra cùng 1 kết quả cho cùng input).
     *
     * Khi $switchRoomTypeId là null và loại phòng hiện tại hết chỗ cho những
     * đêm gia hạn: nếu đơn chỉ có 1 BookingItem, KHÔNG throw ngay — trả về
     * `needs_switch=true` kèm `alternatives` (loại phòng khác còn trống +
     * phòng vật lý cụ thể + giá) để lễ tân chọn. Đơn nhiều BookingItem hoặc
     * không có phương án nào thay thế vẫn throw như cũ.
     *
     * @return array{needs_switch: bool, nights_added: int, extra_amount?: float, items?: array<int, array{item: BookingItem, pricing: array}>, alternatives?: array, switch?: array}
     */
    private function computeExtension(Booking $booking, string $newCheckOut, ?int $switchRoomTypeId = null, ?int $switchRoomId = null): array
    {
        if ($booking->status !== BookingStatus::CHECKED_IN) {
            throw ValidationException::withMessages([
                'new_check_out' => ['Chỉ có thể gia hạn cho đơn đang lưu trú (đã check-in).'],
            ]);
        }

        $oldCheckOut = $booking->check_out->toDateString();

        if ($newCheckOut <= $oldCheckOut) {
            throw ValidationException::withMessages([
                'new_check_out' => ["Ngày trả phòng mới phải sau ngày trả phòng hiện tại ({$booking->check_out->format('d/m/Y')})."],
            ]);
        }

        $items = $booking->bookingItems()->with('roomType')->get();

        if ($switchRoomTypeId) {
            if ($items->count() !== 1) {
                throw ValidationException::withMessages([
                    'new_check_out' => ['Đơn có nhiều loại phòng, không thể tự động đổi loại phòng — vui lòng xử lý thủ công.'],
                ]);
            }

            $item = $items->first();

            $roomType = RoomType::where('status', 'active')->find($switchRoomTypeId);
            if (! $roomType || $roomType->id === $item->room_type_id) {
                throw ValidationException::withMessages([
                    'new_check_out' => ['Loại phòng đổi sang không hợp lệ.'],
                ]);
            }

            if ($item->adults > $roomType->capacity) {
                throw ValidationException::withMessages([
                    'new_check_out' => ["Phòng \"{$roomType->name}\" tối đa {$roomType->capacity} người lớn, không đủ chỗ cho {$item->adults} người lớn của đơn này."],
                ]);
            }

            $extraBedsNeeded = $this->extraBedsNeeded($roomType, 1, $item->adults, $item->children);
            if ($extraBedsNeeded > 1) {
                throw ValidationException::withMessages([
                    'new_check_out' => ["Phòng \"{$roomType->name}\" không đủ sức chứa cho {$item->adults} người lớn + {$item->children} trẻ em của đơn này, kể cả khi dùng giường phụ."],
                ]);
            }

            if ($extraBedsNeeded > 0 && $this->extraBedInventoryService->countAvailable($oldCheckOut, $newCheckOut) < $extraBedsNeeded) {
                throw ValidationException::withMessages([
                    'new_check_out' => ["Phòng \"{$roomType->name}\" cần giường phụ cho khoảng ngày này nhưng khách sạn đã hết giường phụ, vui lòng chọn phương án khác."],
                ]);
            }

            $availability = $this->availabilityService->check($roomType->id, $oldCheckOut, $newCheckOut, 1, null, $booking->id);
            if (! $availability['can_book']) {
                throw ValidationException::withMessages([
                    'new_check_out' => ["Phòng \"{$roomType->name}\" vừa hết chỗ trống, vui lòng chọn phương án khác."],
                ]);
            }

            $room = Room::where('room_type_id', $roomType->id)->find($switchRoomId);
            if (! $room || $room->isOccupied()) {
                throw ValidationException::withMessages([
                    'new_check_out' => ['Phòng vật lý đã chọn không hợp lệ hoặc vừa có khách khác nhận.'],
                ]);
            }

            $pricing = $this->pricingService->calculate($roomType, $oldCheckOut, $newCheckOut, 1, $item->children, $extraBedsNeeded);

            return [
                'needs_switch' => false,
                'nights_added' => $pricing['nights'],
                'extra_amount' => $pricing['total_price'],
                'switch'       => [
                    'room_type'         => $roomType,
                    'room'              => $room,
                    'pricing'           => $pricing,
                    'old_item'          => $item,
                    'extra_beds_needed' => $extraBedsNeeded,
                ],
            ];
        }

        $failedItems = [];
        foreach ($items as $item) {
            $availability = $this->availabilityService->check(
                $item->room_type_id, $oldCheckOut, $newCheckOut, $item->quantity, null, $booking->id
            );

            if (! $availability['can_book']) {
                $failedItems[] = $item;
            }
        }

        if ($failedItems !== []) {
            if (count($items) === 1) {
                $alternatives = $this->findSwitchAlternatives($failedItems[0], $oldCheckOut, $newCheckOut);

                if ($alternatives !== []) {
                    return [
                        'needs_switch' => true,
                        'alternatives' => $alternatives,
                        'nights_added' => $this->pricingService->nightCount($oldCheckOut, $newCheckOut),
                    ];
                }
            }

            $messages = array_map(
                fn (BookingItem $item) => "Phòng \"{$item->roomType->name}\" không đủ trống để gia hạn tới ngày " . \Carbon\Carbon::parse($newCheckOut)->format('d/m/Y') . '.',
                $failedItems
            );

            throw ValidationException::withMessages(['new_check_out' => $messages]);
        }

        $nightsAdded = null;
        $extraAmount = 0.0;
        $lines       = [];

        foreach ($items as $item) {
            $pricing = $this->pricingService->calculate($item->roomType, $oldCheckOut, $newCheckOut, $item->quantity, $item->children, $item->extra_beds);

            $nightsAdded ??= $pricing['nights'];
            $extraAmount += $pricing['total_price'];

            $currentRoomNumber = $item->bookingItemRooms()->whereNull('checked_out_at')->with('room')->first()?->room?->room_number;

            $lines[] = ['item' => $item, 'pricing' => $pricing, 'room_number' => $currentRoomNumber];
        }

        return [
            'needs_switch'         => false,
            'nights_added'         => $nightsAdded,
            'extra_amount'         => $extraAmount,
            'items'                => $lines,
            'switch_alternatives'  => count($items) === 1 ? $this->findSwitchAlternatives($items->first(), $oldCheckOut, $newCheckOut) : [],
        ];
    }

    /**
     * Các loại phòng khác (đang active, đủ sức chứa) còn trống cho khoảng
     * ngày gia hạn khi loại phòng hiện tại của $item đã hết chỗ, hoặc khi lễ
     * tân muốn tự nguyện đổi phòng — dùng để lễ tân chọn phương án đổi phòng
     * thay vì tự động chọn hộ (đã chốt với người dùng). Chỉ giữ những loại
     * phòng có ít nhất 1 phòng vật lý đang không có khách ngay lúc gọi
     * (roomService->availableForRoomType()) — đây là phòng cụ thể sẽ được
     * gán ngay khi lễ tân xác nhận đổi.
     *
     * Sức chứa xét theo ĐÚNG rule dùng lúc tạo đơn (xem extraBedsNeeded()/
     * validateGuestCapacity()): người lớn luôn bị chặn cứng theo capacity
     * (không loại trừ), trẻ em dư ra được bù bằng tối đa 1 giường phụ/phòng
     * — trước đây lọc cứng `capacity >= adults+children` khiến hầu hết loại
     * phòng nhỏ (capacity 2) bị loại oan dù có thể bù bằng giường phụ, chỉ
     * còn mỗi Family (capacity 4) lọt qua.
     *
     * @return array<int, array{room_type_id: int, name: string, extra_amount: float, extra_beds_needed: int, available_rooms: array<int, array{id: int, room_number: string}>}>
     */
    private function findSwitchAlternatives(BookingItem $item, string $checkIn, string $checkOut): array
    {
        $candidates = RoomType::where('status', 'active')
            ->where('id', '!=', $item->room_type_id)
            ->where('capacity', '>=', $item->adults)
            ->get();

        $extraBedAvailable = null;

        $alternatives = [];

        foreach ($candidates as $roomType) {
            $extraBedsNeeded = $this->extraBedsNeeded($roomType, 1, $item->adults, $item->children);

            if ($extraBedsNeeded > 1) {
                continue;
            }

            if ($extraBedsNeeded > 0) {
                $extraBedAvailable ??= $this->extraBedInventoryService->countAvailable($checkIn, $checkOut);

                if ($extraBedAvailable < $extraBedsNeeded) {
                    continue;
                }
            }

            $availability = $this->availabilityService->check($roomType->id, $checkIn, $checkOut, 1, null, $item->booking_id);

            if (! $availability['can_book']) {
                continue;
            }

            $availableRooms = $this->roomService->availableForRoomType($roomType->id);

            if ($availableRooms->isEmpty()) {
                continue;
            }

            $pricing = $this->pricingService->calculate($roomType, $checkIn, $checkOut, 1, $item->children, $extraBedsNeeded);

            $alternatives[] = [
                'room_type_id'       => $roomType->id,
                'name'               => $roomType->name,
                'extra_amount'       => $pricing['total_price'],
                'extra_beds_needed'  => $extraBedsNeeded,
                'available_rooms'    => $availableRooms->map(fn (Room $room) => [
                    'id'          => $room->id,
                    'room_number' => $room->room_number,
                ])->values()->all(),
            ];
        }

        usort($alternatives, fn ($a, $b) => $a['extra_amount'] <=> $b['extra_amount']);

        return $alternatives;
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

        $this->guardEarlyCheckinApproval($booking);

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

                    BookingItemRoom::create(['booking_item_id' => $item->id, 'room_id' => $roomId, 'checked_in_at' => now()]);
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
     * Chặn check-in TRƯỚC giờ chuẩn nếu đơn chưa có yêu cầu "Nhận phòng sớm"
     * (EarlyCheckinRequest) được admin/staff duyệt — bắt buộc phải đi qua
     * luồng duyệt (EarlyCheckinRequestService) thay vì lễ tân tự ý cho khách
     * vào phòng sớm bất kỳ lúc nào. Không áp dụng nếu khách sạn chưa cấu
     * hình check_in_time, hoặc hôm nay không phải đúng ngày check_in đã đặt
     * (khách đến TRỄ hơn ngày đặt, không phải "sớm" — xem isCheckInDateToday()).
     */
    private function guardEarlyCheckinApproval(Booking $booking): void
    {
        $hotel = HotelInfo::instance();

        if (! $hotel->check_in_time || ! $booking->isCheckInDateToday()) {
            return;
        }

        $nowVn = now('Asia/Ho_Chi_Minh')->format('H:i:s');

        if ($nowVn >= $hotel->check_in_time) {
            return;
        }

        $hasApproved = $booking->earlyCheckinRequests()->where('status', 'approved')->exists();

        if (! $hasApproved) {
            throw ValidationException::withMessages([
                'status' => ['Khách đến trước giờ nhận phòng chuẩn (' . substr($hotel->check_in_time, 0, 5) . ') nhưng chưa có yêu cầu nhận phòng sớm được duyệt. Vui lòng duyệt yêu cầu ở mục "Yêu cầu nhận phòng sớm" trước, hoặc đợi tới giờ chuẩn.'],
            ]);
        }
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

        // Khách vào được tới đây (đã qua guardEarlyCheckinApproval()) nghĩa
        // là có 1 EarlyCheckinRequest đã duyệt cho lần đến sớm này, và phí
        // cố định (100k/giờ) đã thu ngay lúc duyệt (xem
        // EarlyCheckinRequestService::approve()) — không tính thêm % phụ
        // phí tự động ở đây nữa để tránh thu 2 lần cho cùng 1 lần đến sớm.
        if ($booking->earlyCheckinRequests()->where('status', 'approved')->exists()) {
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
     * Check-out — chuyển thẳng sang COMPLETED (bỏ qua trạng thái trung gian
     * CHECKED_OUT) + tự động đánh dấu các phòng đã gán cần dọn (dirty), để
     * buồng phòng biết cần xử lý trước khi nhận khách kế tiếp.
     *
     * Trước đây trả phòng chỉ chuyển sang CHECKED_OUT, còn lại phải đợi
     * admin/staff bấm thêm nút "Đánh dấu hoàn thành" (complete()) mới sang
     * COMPLETED — nhưng canComplete() không có điều kiện gì khác ngoài
     * CHECKED_OUT + đã thanh toán đủ (điều kiện này canCheckOut() đã đảm bảo
     * TRƯỚC khi cho trả phòng), nên bước thủ công này không có tác dụng
     * nghiệp vụ nào thêm — chỉ tạo ra 1 bước dễ bị nhân viên quên bấm. Hậu
     * quả thực tế: ReviewService::reviewableItems()/create() chỉ cho đánh
     * giá khi status===COMPLETED, nên khách trả phòng xong không bao giờ
     * thấy nút "Viết đánh giá" cho tới khi có người nhớ vào bấm hoàn thành.
     * Gộp thẳng vào đây để khách trả phòng xong là đánh giá được ngay.
     * complete()/canComplete() vẫn giữ lại (không xóa) để xử lý các đơn cũ
     * còn kẹt ở CHECKED_OUT từ trước khi có thay đổi này.
     *
     * Phụ phí trả phòng muộn: nếu khách ĐÃ xin phép trước (LateCheckoutRequest
     * được duyệt), phí cố định theo bậc đã ghi vào hóa đơn phát sinh ngay lúc
     * duyệt (LateCheckoutRequestService::approve()) — không tính lại ở đây.
     * Nhưng nếu khách trả phòng muộn mà KHÔNG xin phép trước, trước đây hệ
     * thống không tự tính phí gì cả (dựa hoàn toàn vào việc lễ tân nhớ cộng
     * phụ phí thủ công) — đây là lỗ hổng thất thu thực tế. Thêm lại 1 lớp
     * tính phí tự động DỰ PHÒNG applyLateCheckoutSurchargeIfNeeded(), đối
     * xứng với applyEarlyCheckinSurchargeIfNeeded(): chỉ chạy khi chưa có
     * yêu cầu được duyệt (tránh thu 2 lần), dùng chung bảng phí bậc của
     * LateCheckoutRequestService::calculateFee() để nhất quán với phí duyệt
     * thủ công.
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
            // Phải tính phí trả phòng muộn TRƯỚC khi markPaid() chốt hóa đơn
            // phát sinh — nếu không, khoản phí vừa cộng sẽ bị bỏ sót, không
            // được đánh dấu đã thu cùng đợt. Không áp dụng cho trả phòng sớm
            // (isEarly true nghĩa là sai ngày, không phải trễ giờ).
            $lateFee = $isEarly ? null : $this->applyLateCheckoutSurchargeIfNeeded($booking);

            // Lễ tân bấm "Trả phòng" ở trang xác nhận (đã hiện toàn bộ hóa
            // đơn phát sinh cho khách xem + thu tiền mặt tại quầy) — hành
            // động này VỪA xác nhận đã thu VỪA hoàn tất trả phòng trong 1
            // bước, đúng quy trình "khách thanh toán một lần → hoàn tất
            // check-out". Không ảnh hưởng gì nếu không có hóa đơn phát sinh
            // nào đang mở (markPaid() tự bỏ qua, trả về null).
            $this->incidentalInvoiceService->markPaid($booking, Auth::user());

            $oldStatus = $booking->status;
            $booking->update(['status' => BookingStatus::COMPLETED]);

            $note = $isEarly
                ? "Khách trả phòng SỚM hơn dự kiến (còn " . $booking->nightsRemainingForEarlyCheckout() . " đêm chưa sử dụng, ngày đặt trả phòng: {$booking->check_out->format('d/m/Y')})."
                : 'Khách trả phòng.';
            $this->logStatus($booking, $oldStatus, BookingStatus::COMPLETED, Auth::id(), $note);

            $itemIds = $booking->bookingItems->pluck('id');
            BookingItemRoom::whereIn('booking_item_id', $itemIds)->whereNull('checked_out_at')->update(['checked_out_at' => now()]);

            $roomIds = BookingItemRoom::whereIn('booking_item_id', $itemIds)->pluck('room_id');
            Room::whereIn('id', $roomIds)->update(['housekeeping_status' => 'dirty']);

            $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã trả phòng. Cảm ơn bạn đã lưu trú!"));

            return ['booking' => $booking->fresh(['payment']), 'late_checkout_fee' => $lateFee];
        });
    }

    /**
     * Xem checkOut() — tính phí trả phòng muộn TỰ ĐỘNG khi khách trả phòng
     * sau giờ chuẩn của khách sạn mà KHÔNG có LateCheckoutRequest được duyệt
     * trước — đối xứng với applyEarlyCheckinSurchargeIfNeeded(). Không thu
     * phí nếu khách sạn chưa cấu hình giờ trả phòng chuẩn.
     *
     * @return ?float  Số tiền phụ phí vừa cộng, null nếu không áp dụng.
     */
    private function applyLateCheckoutSurchargeIfNeeded(Booking $booking): ?float
    {
        $hotel = HotelInfo::instance();

        if (! $hotel->check_out_time) {
            return null;
        }

        // Chỉ tính là "trả phòng muộn" khi hôm nay ĐÚNG là ngày check_out đã
        // đặt — trả phòng sớm hơn ngày đặt không đi vào nhánh này (đã bị
        // chặn ở lời gọi $isEarly bên checkOut()), và trả phòng sau cả ngày
        // check_out (ở lại thêm ngày, đã gia hạn qua extendStay()) là tình
        // huống khác, ngoài phạm vi phụ phí trả phòng muộn trong ngày.
        if (! $booking->isCheckOutDateToday()) {
            return null;
        }

        $nowVn = now('Asia/Ho_Chi_Minh')->format('H:i:s');
        $standardTime = substr($hotel->check_out_time, 0, 5);

        if ($nowVn <= $hotel->check_out_time) {
            return null;
        }

        // Khách vào được tới đây nghĩa là KHÔNG có LateCheckoutRequest đã
        // duyệt (nếu có, phí bậc cố định đã thu ngay lúc duyệt — xem
        // LateCheckoutRequestService::approve()) — không tính thêm phí tự
        // động ở đây nữa để tránh thu 2 lần cho cùng 1 lần trả muộn.
        if ($booking->lateCheckoutRequests()->where('status', 'approved')->exists()) {
            return null;
        }

        $standard = \Carbon\Carbon::createFromFormat('H:i', $standardTime);
        $now = \Carbon\Carbon::createFromFormat('H:i', substr($nowVn, 0, 5));
        // absolute=true tường minh — diffInMinutes() mặc định trả giá trị CÓ
        // DẤU từ Carbon 3 trở đi (xem LateCheckoutRequestService::create()).
        $hoursLate = round($now->diffInMinutes($standard, true) / 60, 2);
        $isAfterEighteen = substr($nowVn, 0, 5) >= '18:00';

        // Dùng nightly_total của ĐÊM CUỐI CÙNG trong price_breakdown — cùng
        // quy ước LateCheckoutRequestService::create() dùng để tính phí lúc
        // khách xin phép trước, đảm bảo phí tự động và phí duyệt thủ công ra
        // cùng 1 kết quả cho cùng 1 mức độ trễ.
        $lastNightTotal = $booking->bookingItems->sum(function (BookingItem $item) {
            $breakdown = $item->price_breakdown ?? [];
            $lastNight = $breakdown !== [] ? (end($breakdown)['nightly_total'] ?? $item->price_per_night) : $item->price_per_night;

            return (float) $lastNight * $item->quantity;
        });

        $fee = LateCheckoutRequestService::calculateFee($hoursLate, $isAfterEighteen, $lastNightTotal);

        if ($fee > 0) {
            $this->addSurcharge(
                $booking,
                $fee,
                "Trả phòng muộn (tự động) sau giờ tiêu chuẩn {$standardTime} (lúc " . substr($nowVn, 0, 5) . '), không có yêu cầu xin phép trước.'
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
                $itemIds = $booking->bookingItems->pluck('id');
                BookingItemRoom::whereIn('booking_item_id', $itemIds)->whereNull('checked_out_at')->update(['checked_out_at' => now()]);

                $roomIds = BookingItemRoom::whereIn('booking_item_id', $itemIds)->pluck('room_id');
                Room::whereIn('id', $roomIds)->update(['housekeeping_status' => 'dirty']);
            }
        });

        // Gọi ngoài transaction ở trên — đây là 1 lời gọi HTTP ra ngoài
        // (API hoàn tiền VNPay), không nên giữ transaction DB mở trong lúc
        // chờ network.
        $refundOk = $this->attemptRefund($booking, Auth::id());

        return ['booking' => $booking->fresh(['payment']), 'refund_ok' => $refundOk];
    }

    /**
     * Khoảng đệm (phút) giữ phòng thêm sau khi hold hết hạn, trước khi hủy
     * hẳn — xem processBookingExpiry().
     */
    private function expiredGraceMinutes(): int
    {
        return (int) config('services.booking.expired_grace_minutes', 5);
    }

    /**
     * Tự động hủy các đơn đã quá hạn giữ chỗ (kể cả khoảng đệm
     * expired_pending_check) — nhả phòng lại cho khách khác (xử lý ngầm định
     * qua BookingStatus::holdingStatuses(), đơn cancelled không còn tính vào
     * tồn kho). Gọi từ CancelExpiredDepositBookings command (scheduled mỗi
     * phút — xem routes/console.php).
     *
     * @return array{moved_to_grace: int, cancelled: int}
     */
    public function cancelExpiredDepositBookings(): array
    {
        $candidateIds = Booking::where(function ($q) {
                $q->where('status', BookingStatus::PENDING_DEPOSIT)
                    ->where('deposit_expires_at', '<=', now());
            })
            ->orWhere(function ($q) {
                $q->where('status', BookingStatus::EXPIRED_PENDING_CHECK)
                    ->where('expired_pending_check_at', '<=', now()->subMinutes($this->expiredGraceMinutes()));
            })
            ->pluck('id');

        $movedToGrace = 0;
        $cancelled    = 0;

        foreach ($candidateIds as $bookingId) {
            $result = $this->processBookingExpiry($bookingId);
            $movedToGrace += $result['moved_to_grace'] ? 1 : 0;
            $cancelled    += $result['cancelled'] ? 1 : 0;
        }

        return ['moved_to_grace' => $movedToGrace, 'cancelled' => $cancelled];
    }

    /**
     * Quét các đơn đang lưu trú (CHECKED_IN) đã quá hạn trả phòng (giờ chuẩn
     * hoặc giờ đã duyệt trả muộn — xem Booking::isOverdueCheckout()) mà
     * khách vẫn CHƯA trả phòng, thông báo 1 LẦN cho admin/staff — dedup qua
     * overdue_checkout_notified_at để job quét lặp lại nhiều lần trong ngày
     * không spam thông báo trùng cho cùng 1 đơn. Gọi từ FlagOverdueCheckouts
     * command (scheduled — xem routes/console.php). Màu đỏ trên trang Phòng
     * vật lý không phụ thuộc job này, luôn tính real-time lúc tải trang.
     *
     * @return int Số đơn vừa được gắn cờ + thông báo lần đầu.
     */
    public function flagOverdueCheckouts(): int
    {
        $bookings = Booking::where('status', BookingStatus::CHECKED_IN)
            ->whereNull('overdue_checkout_notified_at')
            ->with('lateCheckoutRequests')
            ->get()
            ->filter(fn (Booking $b) => $b->isOverdueCheckout());

        if ($bookings->isEmpty()) {
            return 0;
        }

        $staffAndAdmins = User::whereIn('role', ['admin', 'staff'])->get();

        foreach ($bookings as $booking) {
            $booking->update(['overdue_checkout_notified_at' => now()]);
            $staffAndAdmins->each(fn (User $u) => $u->notify(new OverdueCheckout($booking)));
        }

        return $bookings->count();
    }

    /**
     * Xử lý quá hạn giữ chỗ cho 1 booking — 2 bước, cùng 1 lock:
     *
     *   1. pending_deposit + deposit_expires_at đã qua  → expired_pending_check
     *      (bắt đầu đếm khoảng đệm expiredGraceMinutes(), CHƯA đụng tới
     *      payment — nếu đang PENDING giữa chừng VNPay thì vẫn để nguyên,
     *      để confirmVnpayReturn() còn có thể xác nhận nếu IPN tới trong lúc
     *      đệm này).
     *   2. expired_pending_check + đã hết luôn khoảng đệm → cancelled thật
     *      sự, nhả phòng (payment PENDING → UNPAID nếu vẫn dở dang).
     *
     * Cả 2 bước chạy trong CÙNG 1 transaction + lockForUpdate() nên nếu job
     * quét bị trễ nhiều vòng, 1 booking có thể nhảy thẳng bước 1 → bước 2
     * trong 1 lần gọi — vẫn đúng vì mỗi bước tự re-check điều kiện theo mốc
     * thời gian tuyệt đối, không phụ thuộc tần suất gọi.
     *
     * Dùng chung bởi cancelExpiredDepositBookings() (job quét mỗi phút) VÀ
     * trực tiếp tại các điểm khách bấm thanh toán/báo chuyển khoản
     * (payDepositDemo/initiateVnpayPayment/markBankTransferPending) — job
     * quét định kỳ có độ trễ, gọi hàm này ngay tại điểm xử lý để tự chuyển
     * trạng thái ngay khi phát hiện quá hạn thay vì chờ job.
     *
     * @return array{moved_to_grace: bool, cancelled: bool}
     */
    private function processBookingExpiry(int $bookingId): array
    {
        return DB::transaction(function () use ($bookingId) {
            $booking = Booking::with('payment')->whereKey($bookingId)->lockForUpdate()->first();

            if (! $booking) {
                return ['moved_to_grace' => false, 'cancelled' => false];
            }

            $movedToGrace = false;

            if ($booking->status === BookingStatus::PENDING_DEPOSIT && $booking->deposit_expires_at?->isPast()) {
                $oldStatus = $booking->status;
                $booking->update(['status' => BookingStatus::EXPIRED_PENDING_CHECK, 'expired_pending_check_at' => now()]);
                $this->logStatus($booking, $oldStatus, BookingStatus::EXPIRED_PENDING_CHECK, null, 'Hết hạn giữ chỗ (' . self::DEPOSIT_HOLD_MINUTES . ' phút) — giữ thêm ' . $this->expiredGraceMinutes() . ' phút đệm để chờ xác nhận thanh toán VNPay trước khi hủy hẳn.');
                $movedToGrace = true;
            }

            if ($booking->status === BookingStatus::EXPIRED_PENDING_CHECK && $booking->expired_pending_check_at?->lte(now()->subMinutes($this->expiredGraceMinutes()))) {
                $oldStatus = $booking->status;
                $booking->update(['status' => BookingStatus::CANCELLED]);
                $this->logStatus($booking, $oldStatus, BookingStatus::CANCELLED, null, 'Tự động hủy — quá hạn giữ chỗ (' . self::DEPOSIT_HOLD_MINUTES . ' phút) kể cả ' . $this->expiredGraceMinutes() . ' phút đệm mà vẫn chưa xác nhận được thanh toán.');

                if ($booking->payment && $booking->payment->status === PaymentStatus::PENDING) {
                    $oldPaymentStatus = $booking->payment->status;
                    $booking->payment->update(['status' => PaymentStatus::UNPAID, 'pending_gateway_amount' => null]);
                    $this->logPaymentStatus($booking->payment, $oldPaymentStatus, PaymentStatus::UNPAID, null, 'Đơn tự hủy do quá hạn giữ chỗ, giao dịch thanh toán dở dang bị hủy theo.');
                }

                $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã tự động hủy do quá hạn giữ chỗ chưa đặt cọc/thanh toán."));

                return ['moved_to_grace' => $movedToGrace, 'cancelled' => true];
            }

            return ['moved_to_grace' => $movedToGrace, 'cancelled' => false];
        });
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
    /**
     * Số giường phụ CẦN để hợp thức hóa số khách khai báo — ý nghĩa khác
     * nhau theo category:
     *   - Family: giường phụ tăng thêm giới hạn trẻ em CƠ BẢN (2/phòng), độc
     *     lập với người lớn — phòng đủ lớn (2 giường đôi) để nhận thêm trẻ em
     *     ngoài số người lớn đã ở mức tối đa, không liên quan sức chứa tổng.
     *   - Standard/Superior/Deluxe/Suite: giường phụ bù phần TỔNG khách
     *     (người lớn + trẻ em) vượt sức chứa phòng — phòng nhỏ (capacity 2)
     *     nên người lớn đã chiếm gần hết chỗ, trẻ em dư ra phải bù bằng
     *     giường phụ.
     * Luôn trả về số THẬT SỰ cần (không giới hạn ở $quantity) — nơi gọi tự so
     * với $quantity (tối đa 1 giường/phòng) để quyết có hợp lệ hay không.
     */
    private function extraBedsNeeded(RoomType $roomType, int $quantity, int $adults, int $children): int
    {
        if ($roomType->category === 'family') {
            return max(0, $children - (self::MAX_CHILDREN_PER_ROOM * $quantity));
        }

        return max(0, ($adults + $children) - ($roomType->capacity * $quantity));
    }

    private function validateGuestCapacity(array $items, \Illuminate\Support\Collection $roomTypes): void
    {
        foreach ($items as $index => $item) {
            $roomType = $roomTypes[(int) $item['room_type_id']];
            $quantity = (int) $item['quantity'];
            $adults   = (int) ($item['adults'] ?? 1);
            $children = (int) ($item['children'] ?? 0);
            $infants  = (int) ($item['infants'] ?? 0);
            $capacity = $roomType->capacity * $quantity;
            $maxChildren = self::MAX_CHILDREN_PER_ROOM * $quantity;
            $maxInfants  = self::MAX_INFANTS_PER_ROOM * $quantity;
            $isFamily = $roomType->category === 'family';

            // Người lớn LUÔN bị chặn cứng theo capacity, kể cả Family — giường
            // phụ KHÔNG bao giờ được dùng để nhét thêm người lớn, chỉ dành cho
            // trẻ em (6-11 tuổi) ngủ riêng 1 giường. Không có ngoại lệ nào.
            if ($adults > $capacity) {
                throw ValidationException::withMessages([
                    "items.{$index}.adults" => ["Phòng \"{$roomType->name}\" tối đa {$capacity} người lớn ({$roomType->capacity} khách/phòng × {$quantity} phòng), nhưng khai báo {$adults} người lớn. Giường phụ chỉ dành cho trẻ em (6-11 tuổi), không dùng để thêm người lớn — vui lòng đặt thêm phòng hoặc đổi loại phòng lớn hơn."],
                ]);
            }

            // Trẻ sơ sinh: giới hạn ĐỒNG NHẤT mọi category, không có giường
            // phụ/ngoại lệ nào bù được (khác trẻ em 6-11 tuổi) — kiểm tra
            // trước nhánh Family/thường vì áp dụng như nhau cho cả hai.
            if ($infants > $maxInfants) {
                throw ValidationException::withMessages([
                    "items.{$index}.infants" => ["Phòng \"{$roomType->name}\" tối đa " . self::MAX_INFANTS_PER_ROOM . " trẻ sơ sinh (0-5 tuổi)/phòng × {$quantity} phòng = {$maxInfants}, nhưng khai báo {$infants} trẻ sơ sinh."],
                ]);
            }

            // Family: người lớn và trẻ em xét RIÊNG (không cộng chung vào 1
            // sức chứa như các category khác) — xem extraBedsNeeded().
            if ($isFamily) {
                $needed = $this->extraBedsNeeded($roomType, $quantity, $adults, $children);

                if ($needed > 0) {
                    if (! empty($item['extra_bed']) && $needed <= $quantity) {
                        continue;
                    }

                    $hint = ' Tick "Cần giường phụ" để tăng lên tối đa ' . ($maxChildren + $quantity) . ' trẻ em/phòng.';

                    throw ValidationException::withMessages([
                        "items.{$index}.children" => ["Phòng \"{$roomType->name}\" tối đa {$maxChildren} trẻ em (6-11 tuổi)/phòng × {$quantity} phòng, nhưng khai báo {$children} trẻ em." . $hint],
                    ]);
                }

                continue;
            }

            if ($children > $maxChildren) {
                throw ValidationException::withMessages([
                    "items.{$index}.children" => ["Phòng \"{$roomType->name}\" tối đa " . self::MAX_CHILDREN_PER_ROOM . " trẻ em (6-11 tuổi)/phòng × {$quantity} phòng = {$maxChildren}, nhưng khai báo {$children} trẻ em."],
                ]);
            }

            if ($adults + $children > $capacity) {
                // Tới đây $adults đã chắc chắn <= $capacity (kiểm tra trên) nên
                // phần vượt chỉ có thể do trẻ em — đúng ý nghĩa "giường phụ
                // dành cho trẻ em" khi bù qua nhánh dưới.
                $excess = $this->extraBedsNeeded($roomType, $quantity, $adults, $children);

                // Category có nhánh giường phụ (Standard/Superior/Deluxe/Suite
                // — xem RoomType::supportsExtraBed()) được bù phần trẻ em vượt qua
                // giường phụ (tối đa 1 giường/phòng) NẾU khách đã tick "Cần
                // giường phụ" cho dòng này — không throw, để BookingService::
                // create() tự quyết CONFIRMED hay PENDING_CONSULTATION dựa
                // trên tồn kho thật (ở đây chỉ xác nhận ĐIỀU KIỆN được phép
                // yêu cầu).
                if ($roomType->supportsExtraBed() && ! empty($item['extra_bed']) && $excess <= $quantity) {
                    continue;
                }

                $hint = $roomType->supportsExtraBed()
                    ? ' Tick "Cần giường phụ" nếu muốn bù phần trẻ em vượt sức chứa bằng giường phụ.'
                    : '';

                throw ValidationException::withMessages([
                    "items.{$index}.adults" => ["Phòng \"{$roomType->name}\" tối đa {$capacity} khách ({$roomType->capacity} khách/phòng × {$quantity} phòng), nhưng khai báo {$adults} người lớn + {$children} trẻ em (trẻ sơ sinh dưới 6 tuổi không tính vào sức chứa)." . $hint],
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
     * $feePercent (0/30/50/100 — xem Booking::cancellationFeePercent()) trừ
     * thẳng % tương ứng của Booking::total_amount ra khỏi số tiền được hoàn
     * TRƯỚC khi tính toán, bất kể phương thức thanh toán — nhưng KHÔNG bao
     * giờ vượt quá số tiền đã thực thu (amount_collected): khách chỉ mất tối
     * đa những gì đã trả, hệ thống không đòi thêm phần thiếu nếu phí hủy tính
     * ra lớn hơn số tiền cọc/đã trả.
     *
     * @return bool true nếu đã hoàn xong (hoặc không cần hoàn tự động qua
     *              cổng — chuyển khoản/tiền mặt); false nếu ĐÁNG LẼ phải tự
     *              động hoàn qua cổng nhưng không thực hiện được (lỗi mạng,
     *              cổng từ chối, hoặc thiếu thông tin giao dịch) — nơi gọi
     *              hàm này cần báo rõ cho người dùng biết để xử lý thủ công,
     *              KHÔNG được coi là đã hoàn tiền thành công.
     */
    private function attemptRefund(Booking $booking, ?int $actorId, int $feePercent = 0): bool
    {
        $payment = $booking->payment;

        if (! $payment) {
            return true;
        }

        $oldStatus = $payment->status;
        $collected = (float) $payment->amount_collected;

        // Với VNPay, xét theo amount_collected (tiền THẬT đã thu qua cổng)
        // thay vì payment->canRefund() (dựa trên status hiện tại) —
        // applyExtraCharge() có thể đã mở lại PAID→PENDING do phát sinh phụ
        // phí SAU khi thanh toán xong, nhưng tiền cũ vẫn đang nằm ở VNPay và
        // vẫn phải hoàn khi hủy đơn, bất kể status lúc này không còn là PAID
        // nữa. Nếu chưa từng thu được đồng nào thì không có gì để hoàn/giữ.
        if ($collected <= 0) {
            return true;
        }

        $forfeitAmount = $feePercent > 0
            ? min($collected, round((float) $booking->total_amount * $feePercent / 100, 2))
            : 0.0;
        $refundableTotal = round($collected - $forfeitAmount, 2);
        $forfeitNote = $forfeitAmount > 0
            ? " Phí hủy {$feePercent}% theo chính sách hủy — giữ lại " . number_format($forfeitAmount, 0, ',', '.') . 'đ.'
            : '';

        if ($payment->method === PaymentMethod::ONLINE_VNPAY) {
            if ($refundableTotal <= 0) {
                $payment->update(['status' => PaymentStatus::REFUNDED, 'amount_collected' => 0, 'last_gateway_amount' => null]);
                $this->logPaymentStatus($payment, $oldStatus, PaymentStatus::REFUNDED, $actorId, 'Hủy đơn — số tiền đã thu không vượt quá phí hủy, giữ lại toàn bộ, không có phần hoàn.' . $forfeitNote);

                return true;
            }

            if (! $payment->gateway_transaction_no) {
                $this->logPaymentStatus($payment, $oldStatus, $oldStatus, $actorId, 'Thanh toán VNPay thiếu thông tin giao dịch cổng (chưa từng được VNPay xác nhận thật) — không thể tự động hoàn tiền, cần xử lý hoàn tiền thủ công.' . $forfeitNote);

                return false;
            }

            // transaction_code/gateway_transaction_no chỉ lưu được giao dịch
            // VNPay GẦN NHẤT — nếu payment đã trải qua nhiều chu kỳ thanh
            // toán VNPay riêng biệt (trả đủ → phụ phí mở lại PENDING → trả
            // tiếp qua VNPay lần 2), refundableTotal có thể lớn hơn số tiền
            // giao dịch hiện tại thực thu (last_gateway_amount). Chỉ được
            // yêu cầu API hoàn tối đa bằng số tiền của ĐÚNG giao dịch đang
            // lưu, tránh VNPay từ chối/hoàn sai do vượt quá số giao dịch gốc.
            $refundable = min($refundableTotal, (float) ($payment->last_gateway_amount ?? $refundableTotal));
            $strandedFromEarlierTxn = round($refundableTotal - $refundable, 2);

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
                $this->logPaymentStatus($payment, $oldStatus, $oldStatus, $actorId, 'Gọi API hoàn tiền VNPay lỗi: ' . $e->getMessage() . ' — cần xử lý hoàn tiền thủ công.' . $forfeitNote);

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
                        . number_format($strandedFromEarlierTxn, 0, ',', '.') . 'đ từ (các) giao dịch trước đó không còn thông tin cổng để tự động hoàn, cần xử lý hoàn tiền thủ công.' . $forfeitNote);

                    return false;
                }

                $payment->update(['status' => PaymentStatus::REFUNDED, 'amount_collected' => 0, 'last_gateway_amount' => null]);
                $this->logPaymentStatus($payment, $oldStatus, PaymentStatus::REFUNDED, $actorId, 'Hoàn tiền tự động qua VNPay thành công.' . $forfeitNote);

                return true;
            }

            $this->logPaymentStatus($payment, $oldStatus, $oldStatus, $actorId, 'Hoàn tiền tự động qua VNPay thất bại (mã ' . ($response['vnp_ResponseCode'] ?? '?') . ') — cần xử lý hoàn tiền thủ công.' . $forfeitNote);

            return false;
        }

        // Các phương thức thủ công (chuyển khoản, tiền mặt...): dựa theo
        // amount_collected (được updatePaymentStatus() ghi nhận khi đánh dấu
        // PAID) thay vì status hiện tại — applyExtraCharge() có thể đã mở lại
        // PAID→PENDING do phát sinh phụ phí SAU khi thanh toán xong, nhưng
        // khoản tiền mặt/chuyển khoản cũ khách đã nộp vẫn cần được hoàn khi
        // hủy, bất kể status lúc này không còn là PAID nữa (tiền cọc giữ chỗ
        // — chưa từng có amount_collected — vẫn không tự động hoàn, đúng
        // chính sách cũ). Hệ thống không tự chuyển tiền cho phương thức thủ
        // công — chỉ đánh dấu REFUNDED + ghi rõ số tiền staff cần hoàn tay.
        $payment->update(['status' => PaymentStatus::REFUNDED, 'amount_collected' => 0]);
        $manualNote = match (true) {
            $forfeitAmount <= 0 => 'Tự động hoàn tiền khi hủy đơn.',
            $refundableTotal <= 0 => "Hủy đơn — phí hủy {$feePercent}%, giữ lại toàn bộ " . number_format($forfeitAmount, 0, ',', '.') . 'đ, không có phần hoàn.',
            default => "Hủy đơn — phí hủy {$feePercent}%, giữ lại " . number_format($forfeitAmount, 0, ',', '.') . 'đ, cần hoàn thủ công phần còn lại ' . number_format($refundableTotal, 0, ',', '.') . 'đ cho khách.',
        };
        $this->logPaymentStatus($payment, $oldStatus, PaymentStatus::REFUNDED, $actorId, $manualNote);

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

    /**
     * Xác nhận đơn ngay sau khi khách/staff hoàn tất cọc 30% hoặc thanh
     * toán đủ — chuyển pending_deposit → confirmed và xóa hạn giữ chỗ
     * (deposit_expires_at) vì đơn không còn nguy cơ bị tự hủy nữa. Gọi từ
     * payDepositDemo(), confirmVnpayReturn() (nhánh thành công) và
     * updatePaymentStatus() (admin/staff xác nhận thủ công) — dùng chung 1
     * nơi để cả 3 đường thanh toán đều nhất quán chuyển trạng thái booking.
     *
     * Không làm gì nếu đơn không còn ở pending_deposit (đã confirmed/hủy
     * từ trước) — idempotent, an toàn gọi lại nhiều lần.
     */
    private function confirmAfterPayment(Booking $booking, ?int $actorId): void
    {
        // EXPIRED_PENDING_CHECK cũng phải được xác nhận bình thường ở đây —
        // đó là booking đã hết hạn hold nhưng đang trong khoảng đệm chờ VNPay
        // (xem processBookingExpiry()), thanh toán thành công tới trong lúc
        // đệm này là kịch bản MONG MUỐN, không khác gì PENDING_DEPOSIT chưa
        // hết hạn.
        if (! in_array($booking->status, [BookingStatus::PENDING_DEPOSIT, BookingStatus::EXPIRED_PENDING_CHECK], true)) {
            return;
        }

        $oldStatus = $booking->status;
        $booking->update(['status' => BookingStatus::CONFIRMED, 'deposit_expires_at' => null, 'expired_pending_check_at' => null]);
        $this->logStatus($booking, $oldStatus, BookingStatus::CONFIRMED, $actorId, 'Đơn được xác nhận sau khi cọc/thanh toán thành công.');

        $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã được xác nhận."));
    }

    private function generateCode(): string
    {
        do {
            $code = 'HOMI-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}
