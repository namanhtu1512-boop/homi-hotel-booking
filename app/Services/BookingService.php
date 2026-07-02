<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\Payment;
use App\Models\PaymentStatusLog;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private AvailabilityService $availabilityService,
        private PricingService $pricingService,
    ) {}

    // ----------------------------------------------------------------
    // CUSTOMER
    // ----------------------------------------------------------------

    public function create(User $customer, array $data): Booking
    {
        $roomType = RoomType::where('status', 'active')
            ->findOrFail($data['room_type_id']);

        // DateRangeService validate đã được gọi qua AvailabilityService
        $this->availabilityService->validateDates($data['check_in'], $data['check_out']);

        return DB::transaction(function () use ($customer, $data, $roomType) {
            if (! $this->availabilityService->canBook(
                $roomType->id,
                $data['check_in'],
                $data['check_out'],
                $data['quantity']
            )) {
                throw ValidationException::withMessages([
                    'room_type_id' => ['Phòng đã hết trong khoảng thời gian này.'],
                ]);
            }

            $pricing = $this->pricingService->calculate(
                $roomType,
                $data['check_in'],
                $data['check_out'],
                $data['quantity']
            );

            $booking = Booking::create([
                'user_id'        => $customer->id,
                'booking_code'   => $this->generateCode(),
                'check_in'       => $data['check_in'],
                'check_out'      => $data['check_out'],
                'nights'         => $pricing['nights'],
                'customer_name'  => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? $customer->email,
                'note'           => $data['note'] ?? null,
                'total_amount'   => $pricing['total_price'],
                'status'         => BookingStatus::PENDING,
            ]);

            $this->logStatus($booking, null, BookingStatus::PENDING, $customer->id, 'Khách tạo đơn đặt phòng.');

            $booking->bookingItems()->create([
                'room_type_id'    => $roomType->id,
                'quantity'        => $data['quantity'],
                'price_per_night' => $pricing['unit_price'],
                'nights'          => $pricing['nights'],
                'subtotal'        => $pricing['total_price'],
            ]);

            $payment = $booking->payment()->create([
                'amount' => $pricing['total_price'],
                'status' => PaymentStatus::UNPAID,
                'method' => PaymentMethod::PAY_AT_HOTEL,
            ]);
            $this->logPaymentStatus($payment, null, PaymentStatus::UNPAID, $customer->id, 'Tạo đơn đặt phòng.');

            return $booking->load(['bookingItems.roomType', 'payment']);
        });
    }

    public function myBookings(User $customer, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Booking::where('user_id', $customer->id)
            ->with(['bookingItems.roomType', 'payment'])
            ->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function findForCustomer(int $bookingId, User $customer): Booking
    {
        $booking = Booking::with(['bookingItems.roomType', 'payment'])
            ->findOrFail($bookingId);

        if ($booking->user_id !== $customer->id) {
            abort(403);
        }

        return $booking;
    }

    public function cancelByCustomer(int $bookingId, User $customer): Booking
    {
        $booking = $this->findForCustomer($bookingId, $customer);

        if (! $booking->canCancelByCustomer()) {
            throw ValidationException::withMessages([
                'status' => ['Không thể hủy đơn ở trạng thái hiện tại.'],
            ]);
        }

        $oldStatus = $booking->status;
        $booking->update(['status' => BookingStatus::CANCELLED]);
        $this->logStatus($booking, $oldStatus, BookingStatus::CANCELLED, $customer->id, 'Khách hủy đơn.');

        return $booking->fresh(['payment']);
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

        if (! empty($filters['customer_id'])) {
            $query->where('user_id', $filters['customer_id']);
        }

        if (! empty($filters['booking_code'])) {
            $query->where('booking_code', $filters['booking_code']);
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        return $query->paginate($perPage);
    }

    public function findForAdmin(int $bookingId): Booking
    {
        return Booking::with(['user', 'bookingItems.roomType', 'payment.statusLogs.changedBy', 'statusLogs.changedBy'])
            ->findOrFail($bookingId);
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
        $this->logStatus($booking, $oldStatus, BookingStatus::CONFIRMED, note: 'Admin/staff xác nhận đơn.');

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
        $this->logStatus($booking, $oldStatus, BookingStatus::CANCELLED, note: 'Admin/staff hủy đơn.');

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
