<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\BookingItem;
use App\Models\RoomType;

class AvailabilityService
{
    public function __construct(
        private DateRangeService $dateRange,
    ) {}

    /**
     * Kiểm tra availability cho một room type trong khoảng ngày.
     *
     * @throws \Illuminate\Validation\ValidationException nếu ngày không hợp lệ
     */
    public function check(int $roomTypeId, string $checkIn, string $checkOut, int $quantity = 1): array
    {
        $this->dateRange->validate($checkIn, $checkOut);

        $roomType = RoomType::where('status', 'active')->findOrFail($roomTypeId);

        $bookedQuantity    = $this->getBookedQuantity($roomTypeId, $checkIn, $checkOut);
        $availableQuantity = $roomType->total_rooms - $bookedQuantity;

        return [
            'room_type_id'       => $roomTypeId,
            'check_in'           => $checkIn,
            'check_out'          => $checkOut,
            'nights'             => $this->dateRange->nightCount($checkIn, $checkOut),
            'requested_quantity' => $quantity,
            'available_quantity' => max(0, $availableQuantity),
            'can_book'           => $availableQuantity >= $quantity,
            'total_rooms'        => $roomType->total_rooms,
        ];
    }

    /**
     * Số phòng đã được đặt (pending / confirmed / checked_in) giao nhau với khoảng ngày.
     *
     * BUG FIX: check_in / check_out nằm trên bảng BOOKINGS, không phải booking_items.
     * Điều kiện overlap được đưa vào whereHas('booking') thay vì query trực tiếp booking_items.
     *
     * Overlap condition: booking.check_in < $checkOut AND booking.check_out > $checkIn
     */
    public function getBookedQuantity(int $roomTypeId, string $checkIn, string $checkOut): int
    {
        return (int) BookingItem::where('room_type_id', $roomTypeId)
            ->whereHas('booking', fn ($q) => $q
                ->whereIn('status', BookingStatus::holdingStatuses())
                ->where('check_in', '<', $checkOut)
                ->where('check_out', '>', $checkIn)
            )
            ->sum('quantity');
    }

    /**
     * Dùng bên trong DB transaction để re-check trước khi insert.
     *
     * @throws \Illuminate\Validation\ValidationException nếu ngày không hợp lệ
     */
    public function canBook(int $roomTypeId, string $checkIn, string $checkOut, int $quantity): bool
    {
        return $this->check($roomTypeId, $checkIn, $checkOut, $quantity)['can_book'];
    }

    /**
     * Proxy thuận tiện cho validation ngày — giữ BC cho code gọi trực tiếp.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateDates(string $checkIn, string $checkOut): void
    {
        $this->dateRange->validate($checkIn, $checkOut);
    }
}
