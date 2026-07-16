<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItemRoom;
use App\Models\BookingStatusLog;
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
    public function __construct(
        private AvailabilityService $availabilityService,
        private PricingService $pricingService,
        private PromotionService $promotionService,
        private RoomHoldService $roomHoldService,
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
                    'price_per_night' => $pricing['unit_price'],
                    'nights'          => $pricing['nights'],
                    'subtotal'        => $pricing['room_subtotal'],
                    'child_surcharge' => $pricing['child_surcharge'],
                    'price_breakdown' => $pricing['nightly_breakdown'],
                ];
            }

            $booking = Booking::create([
                'user_id'        => null,
                'booking_code'   => $this->generateCode(),
                'check_in'       => $data['check_in'],
                'check_out'      => $data['check_out'],
                'nights'         => $nights,
                'adults'         => array_sum(array_column($data['items'], 'adults')),
                'children'       => array_sum(array_column($data['items'], 'children')),
                'customer_name'  => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'note'           => $data['note'] ?? null,
                'total_amount'   => $total,
                'discount_amount'=> 0,
                'status'         => BookingStatus::PENDING,
            ]);

            $this->logStatus($booking, null, BookingStatus::PENDING, Auth::id(), 'Admin/staff tạo đơn thủ công.');

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

        // Nạp trước dịch vụ thêm active theo id — findOrFail giữ hành vi 404
        // khi có id không hợp lệ/không active, giống room types ở trên.
        $services = collect($data['services'] ?? [])
            ->mapWithKeys(fn (array $item) => [
                (int) $item['service_id'] => Service::where('status', 'active')
                    ->findOrFail($item['service_id']),
            ]);

        // Kiểm tra sức chứa theo TỪNG loại phòng — mỗi dòng có capacity riêng
        // (roomType.capacity × quantity của chính dòng đó), không gộp chung
        // với các dòng khác trong đơn.
        foreach ($data['items'] as $index => $item) {
            $roomType  = $roomTypes[(int) $item['room_type_id']];
            $quantity  = (int) $item['quantity'];
            $adults    = (int) $item['adults'];
            $children  = (int) ($item['children'] ?? 0);
            $capacity  = $roomType->capacity * $quantity;

            if ($adults + $children > $capacity) {
                throw ValidationException::withMessages([
                    "items.{$index}.adults" => ["Phòng \"{$roomType->name}\" tối đa {$capacity} khách ({$roomType->capacity} khách/phòng × {$quantity} phòng), nhưng bạn khai báo {$adults} người lớn + {$children} trẻ em."],
                ]);
            }
        }

        return DB::transaction(function () use ($customer, $data, $roomTypes, $services, $holdSessionId) {
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
            $lines          = [];

            foreach ($data['items'] as $item) {
                $roomType = $roomTypes[(int) $item['room_type_id']];
                $quantity = (int) $item['quantity'];
                $adults   = (int) $item['adults'];
                $children = (int) ($item['children'] ?? 0);

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

                $lines[] = [
                    'room_type_id'    => $roomType->id,
                    'quantity'        => $quantity,
                    'adults'          => $adults,
                    'children'        => $children,
                    'price_per_night' => $pricing['unit_price'],
                    'nights'          => $pricing['nights'],
                    'subtotal'        => $pricing['room_subtotal'],
                    'child_surcharge' => $pricing['child_surcharge'],
                    'price_breakdown' => $pricing['nightly_breakdown'],
                ];
            }

            // Dịch vụ thêm cộng thẳng vào $total TRƯỚC vòng khuyến mãi bên
            // dưới — khuyến mãi áp dụng lên tổng phòng + dịch vụ, không chỉ
            // riêng tiền phòng (quyết định nghiệp vụ đã chốt).
            $serviceLines = [];

            foreach ($data['services'] ?? [] as $item) {
                $service  = $services[(int) $item['service_id']];
                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $subtotal = (float) $service->price * $quantity;

                $total += $subtotal;

                $serviceLines[] = [
                    'service_id' => $service->id,
                    'quantity'   => $quantity,
                    'unit_price' => $service->price,
                    'subtotal'   => $subtotal,
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
                'customer_name'   => $data['customer_name'],
                'customer_phone'  => $data['customer_phone'],
                'customer_email'  => $data['customer_email'] ?? $customer->email,
                'note'            => $data['note'] ?? null,
                'total_amount'    => $total - $discount,
                'discount_amount' => $discount,
                'status'          => BookingStatus::PENDING,
            ]);

            $this->logStatus($booking, null, BookingStatus::PENDING, $customer->id, 'Khách tạo đơn đặt phòng.');

            // Thông báo cho admin/staff về đơn mới
            User::whereIn('role', ['admin', 'staff'])->each(
                fn (User $u) => $u->notify(new NewBookingReceived($booking))
            );

            foreach ($lines as $line) {
                $booking->bookingItems()->create($line);
            }

            foreach ($serviceLines as $serviceLine) {
                $booking->serviceItems()->create($serviceLine);
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
        $booking = Booking::with(['bookingItems.roomType.images', 'bookingItems.rooms', 'serviceItems.service', 'payment.statusLogs.changedBy', 'promotions'])
            ->findOrFail($bookingId);

        Gate::forUser($customer)->authorize('view', $booking);

        return $booking;
    }

    public function cancelByCustomer(int $bookingId, User $customer): Booking
    {
        $booking = $this->findForCustomer($bookingId, $customer);

        if (! $booking->canCancelByCustomer()) {
            throw ValidationException::withMessages([
                'status' => ['Không thể hủy đơn ở trạng thái hiện tại hoặc đã quá hạn hủy (chỉ hủy được trước ngày nhận phòng).'],
            ]);
        }

        $oldStatus = $booking->status;
        $booking->update(['status' => BookingStatus::CANCELLED]);
        $this->logStatus($booking, $oldStatus, BookingStatus::CANCELLED, $customer->id, 'Khách hủy đơn.');

        $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã được hủy."));

        return $booking->fresh(['payment']);
    }

    /**
     * Khách tự thanh toán online (mô phỏng — chưa nối gateway thật vì chưa
     * có API key VNPay/Momo). Chỉ cho phép khi đơn đã được admin/staff xác
     * nhận, đúng rule sẵn có ở Booking::canMarkPaymentAsPaid().
     */
    public function payOnlineDemo(int $bookingId, User $customer): Booking
    {
        $booking = $this->findForCustomer($bookingId, $customer);

        if (! $booking->canMarkPaymentAsPaid()) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể thanh toán khi đơn đã được xác nhận và chưa thanh toán.'],
            ]);
        }

        $oldStatus = $booking->payment->status;
        $booking->payment->update([
            'method'           => PaymentMethod::ONLINE_DEMO,
            'status'           => PaymentStatus::PAID,
            'transaction_code' => 'DEMO-' . Str::upper(Str::random(10)),
            'paid_at'          => now(),
        ]);
        $this->logPaymentStatus($booking->payment, $oldStatus, PaymentStatus::PAID, $customer->id, 'Khách thanh toán online (mô phỏng).');

        return $booking->fresh('payment');
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

        if ($newStatus === PaymentStatus::PAID && $booking->status !== BookingStatus::CONFIRMED) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể đánh dấu đã thanh toán khi đơn ở trạng thái đã xác nhận.'],
            ]);
        }

        $booking->payment->update([
            'status'  => $newStatus,
            'paid_at' => $newStatus === PaymentStatus::PAID ? now() : $booking->payment->paid_at,
        ]);
        $this->logPaymentStatus($booking->payment, $oldStatus, $newStatus, Auth::id(), 'Admin/staff cập nhật trạng thái thanh toán.');

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
     * Check-in thật — gán số phòng vật lý cụ thể cho từng dòng đơn (đúng
     * số lượng `quantity` của dòng, phòng phải cùng room_type và hiện
     * không có khách). $roomAssignments khóa theo booking_item_id, giá
     * trị là mảng room_id.
     *
     * @param  array<int, array<int, int>>  $roomAssignments
     *
     * @throws ValidationException
     */
    public function checkIn(Booking $booking, array $roomAssignments): Booking
    {
        if (! $booking->canCheckIn()) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể check-in đơn ở trạng thái đã xác nhận.'],
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
                    $room = Room::where('room_type_id', $item->room_type_id)->find($roomId);

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

            return $booking->fresh(['bookingItems.rooms']);
        });
    }

    /**
     * Check-out — chuyển trạng thái + tự động đánh dấu các phòng đã gán
     * cần dọn (dirty), để buồng phòng biết cần xử lý trước khi nhận khách kế tiếp.
     */
    public function checkOut(Booking $booking): Booking
    {
        if (! $booking->canCheckOut()) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể check-out đơn đang lưu trú.'],
            ]);
        }

        return DB::transaction(function () use ($booking) {
            $oldStatus = $booking->status;
            $booking->update(['status' => BookingStatus::CHECKED_OUT]);
            $this->logStatus($booking, $oldStatus, BookingStatus::CHECKED_OUT, Auth::id(), 'Khách trả phòng.');

            $roomIds = BookingItemRoom::whereIn('booking_item_id', $booking->bookingItems->pluck('id'))->pluck('room_id');
            Room::whereIn('id', $roomIds)->update(['housekeeping_status' => 'dirty']);

            return $booking->fresh();
        });
    }

    public function complete(Booking $booking): Booking
    {
        if (! $booking->canComplete()) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể đánh dấu hoàn thành đơn ở trạng thái đã xác nhận.'],
            ]);
        }

        $oldStatus = $booking->status;
        $booking->update(['status' => BookingStatus::COMPLETED]);
        $this->logStatus($booking, $oldStatus, BookingStatus::COMPLETED, Auth::id(), 'Admin/staff đánh dấu đơn hoàn thành.');

        $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã hoàn thành. Cảm ơn bạn đã lưu trú!"));

        return $booking->fresh();
    }

    public function cancelByAdmin(Booking $booking): Booking
    {
        if (! $booking->canCancelByAdmin()) {
            throw ValidationException::withMessages([
                'status' => ['Không thể hủy đơn ở trạng thái hiện tại.'],
            ]);
        }

        $oldStatus = $booking->status;
        $booking->update(['status' => BookingStatus::CANCELLED]);
        $this->logStatus($booking, $oldStatus, BookingStatus::CANCELLED, Auth::id(), 'Admin/staff hủy đơn.');

        $booking->user?->notify(new BookingStatusChanged($booking, "Đơn {$booking->booking_code} đã bị hủy bởi khách sạn."));

        if ($booking->payment && $booking->payment->canRefund()) {
            $oldPaymentStatus = $booking->payment->status;
            $booking->payment->update(['status' => PaymentStatus::REFUNDED]);
            $this->logPaymentStatus($booking->payment, $oldPaymentStatus, PaymentStatus::REFUNDED, Auth::id(), 'Tự động hoàn tiền khi hủy đơn.');
        }

        return $booking->fresh(['payment']);
    }

    // ----------------------------------------------------------------
    // PRIVATE
    // ----------------------------------------------------------------

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
