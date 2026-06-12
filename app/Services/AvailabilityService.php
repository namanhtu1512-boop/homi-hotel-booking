<?php

namespace App\Services;

use App\Models\BookingItem;
use App\Models\RoomType;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Check availability for a room type over a date range.
     *
     * Overlap condition: existing.check_in < new.check_out AND existing.check_out > new.check_in
     * This correctly handles T6/T7 (adjacent dates are NOT overlapping).
     */
    public function check(int $roomTypeId, string $checkIn, string $checkOut, int $quantity = 1): array
    {
        $roomType = RoomType::where('status', 'active')->findOrFail($roomTypeId);

        $bookedQuantity = $this->getBookedQuantity($roomTypeId, $checkIn, $checkOut);

        $availableQuantity = $roomType->total_rooms - $bookedQuantity;
        $canBook = $availableQuantity >= $quantity;

        return [
            'room_type_id'       => $roomTypeId,
            'check_in'           => $checkIn,
            'check_out'          => $checkOut,
            'requested_quantity' => $quantity,
            'available_quantity' => max(0, $availableQuantity),
            'can_book'           => $canBook,
            'total_rooms'        => $roomType->total_rooms,
        ];
    }

    /**
     * Returns the quantity of rooms booked (pending + confirmed) that overlap the given range.
     */
    public function getBookedQuantity(int $roomTypeId, string $checkIn, string $checkOut): int
    {
        return (int) BookingItem::where('room_type_id', $roomTypeId)
            ->whereHas('booking', fn($q) => $q->whereIn('status', ['pending', 'confirmed']))
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->sum('quantity');
    }

    /**
     * Convenience method for use inside a DB transaction (re-checks with lock if needed).
     */
    public function canBook(int $roomTypeId, string $checkIn, string $checkOut, int $quantity): bool
    {
        return $this->check($roomTypeId, $checkIn, $checkOut, $quantity)['can_book'];
    }

    public function validateDates(string $checkIn, string $checkOut): void
    {
        $in  = Carbon::parse($checkIn);
        $out = Carbon::parse($checkOut);

        if ($in >= $out) {
            abort(422, 'Ngày trả phòng phải sau ngày nhận phòng.');
        }

        if ($in->lt(Carbon::today())) {
            abort(422, 'Ngày nhận phòng không được trước hôm nay.');
        }
    }
}
