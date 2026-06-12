<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
            ->whereHas('hotel', fn($q) => $q->where('status', 'active'))
            ->findOrFail($data['room_type_id']);

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
                'hotel_id'       => $roomType->hotel_id,
                'booking_code'   => $this->generateCode(),
                'check_in'       => $data['check_in'],
                'check_out'      => $data['check_out'],
                'nights'         => $pricing['nights'],
                'customer_name'  => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? $customer->email,
                'note'           => $data['note'] ?? null,
                'total_amount'   => $pricing['total_price'],
                'status'         => 'pending',
            ]);

            $booking->bookingItems()->create([
                'room_type_id'    => $roomType->id,
                'quantity'        => $data['quantity'],
                'price_per_night' => $pricing['unit_price'],
                'nights'          => $pricing['nights'],
                'subtotal'        => $pricing['total_price'],
            ]);

            $booking->payment()->create([
                'amount' => $pricing['total_price'],
                'status' => 'unpaid',
                'method' => 'pay_at_hotel',
            ]);

            return $booking->load(['bookingItems.roomType.hotel', 'payment']);
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
        $booking = Booking::with(['bookingItems.roomType.hotel', 'payment'])
            ->findOrFail($bookingId);

        if ($booking->user_id !== $customer->id) {
            abort(403);
        }

        return $booking;
    }

    public function cancelByCustomer(int $bookingId, User $customer): Booking
    {
        $booking = $this->findForCustomer($bookingId, $customer);

        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            throw ValidationException::withMessages([
                'status' => ['Không thể hủy đơn ở trạng thái hiện tại.'],
            ]);
        }

        $booking->update(['status' => 'cancelled']);

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

        if (! empty($filters['hotel_id'])) {
            $query->where('hotel_id', $filters['hotel_id']);
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

    public function confirm(Booking $booking): Booking
    {
        if ($booking->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể xác nhận đơn ở trạng thái pending.'],
            ]);
        }

        $booking->update(['status' => 'confirmed']);

        return $booking->fresh();
    }

    public function cancelByAdmin(Booking $booking): Booking
    {
        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            throw ValidationException::withMessages([
                'status' => ['Không thể hủy đơn ở trạng thái hiện tại.'],
            ]);
        }

        $booking->update(['status' => 'cancelled']);

        if ($booking->payment && $booking->payment->status === 'paid') {
            $booking->payment->update(['status' => 'refunded']);
        }

        return $booking->fresh(['payment']);
    }

    // ----------------------------------------------------------------
    // PRIVATE
    // ----------------------------------------------------------------

    private function generateCode(): string
    {
        do {
            $code = 'HOMI-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}
