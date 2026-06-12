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

    public function create(User $customer, array $data): Booking
    {
        $roomType = RoomType::where('is_active', true)
            ->whereHas('hotel', fn($q) => $q->where('is_active', true))
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
                'user_id'          => $customer->id,
                'booking_code'     => $this->generateCode(),
                'status'           => 'pending',
                'contact_name'     => $data['contact_name'],
                'contact_phone'    => $data['contact_phone'],
                'contact_email'    => $data['contact_email'] ?? $customer->email,
                'special_requests' => $data['special_requests'] ?? null,
                'total_price'      => $pricing['total_price'],
            ]);

            $booking->bookingItems()->create([
                'room_type_id' => $roomType->id,
                'check_in'     => $data['check_in'],
                'check_out'    => $data['check_out'],
                'quantity'     => $data['quantity'],
                'nights'       => $pricing['nights'],
                'unit_price'   => $pricing['unit_price'],
                'subtotal'     => $pricing['total_price'],
            ]);

            $booking->payment()->create([
                'amount' => $pricing['total_price'],
                'status' => 'pending',
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

        $booking->update(['status' => 'canceled']);

        return $booking->fresh('payment');
    }

    // --- Admin ---

    public function adminList(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Booking::with(['user', 'bookingItems.roomType.hotel', 'payment'])
            ->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['hotel_id'])) {
            $query->whereHas(
                'bookingItems.roomType',
                fn($q) => $q->where('hotel_id', $filters['hotel_id'])
            );
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

        $booking->update(['status' => 'canceled']);

        if ($booking->payment && $booking->payment->status === 'paid') {
            $booking->payment->update(['status' => 'refunded', 'refunded_at' => now()]);
        }

        return $booking->fresh('payment');
    }

    private function generateCode(): string
    {
        do {
            $code = 'HOMI-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}
