<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\BookingItem;
use App\Models\RoomHold;
use App\Models\RoomType;

class AvailabilityService
{
    public function __construct(
        private DateRangeService $dateRange,
    ) {}

    /**
     * Kiểm tra availability cho một room type trong khoảng ngày.
     *
     * $excludeSessionId: session đang giữ hold cho chính khoảng ngày/phòng
     * này không bị tính là "đã bị chiếm" bởi hold của chính nó — chỉ hold từ
     * session khác mới làm giảm availability.
     *
     * @throws \Illuminate\Validation\ValidationException nếu ngày không hợp lệ
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException nếu room_type không tồn tại/không active
     */
    public function check(int $roomTypeId, string $checkIn, string $checkOut, int $quantity = 1, ?string $excludeSessionId = null): array
    {
        $this->dateRange->validate($checkIn, $checkOut);

        $roomType = RoomType::where('status', 'active')->findOrFail($roomTypeId);

        $bookedQuantity    = $this->getBookedQuantity($roomTypeId, $checkIn, $checkOut, $excludeSessionId);
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
     *
     * Dùng whereDate() thay vì where() để so sánh: cột `check_in`/`check_out` là
     * cast 'date' nhưng Eloquent vẫn ghi xuống DB theo $dateFormat đầy đủ
     * (Y-m-d H:i:s) — MySQL tự cắt phần giờ vì kiểu cột DATE, nhưng SQLite (dùng
     * khi chạy test) không ép kiểu nên có thể lưu kèm "00:00:00". whereDate()
     * chuẩn hóa cả hai vế về phần ngày, tránh sai lệch giữa 2 loại DB.
     */
    public function getBookedQuantity(int $roomTypeId, string $checkIn, string $checkOut, ?string $excludeSessionId = null): int
    {
        $booked = (int) BookingItem::where('room_type_id', $roomTypeId)
            ->whereHas('booking', fn ($q) => $q
                ->whereIn('status', BookingStatus::holdingStatuses())
                ->whereDate('check_in', '<', $checkOut)
                ->whereDate('check_out', '>', $checkIn)
            )
            ->sum('quantity');

        $held = (int) RoomHold::where('room_type_id', $roomTypeId)
            ->active()
            ->when($excludeSessionId, fn ($q) => $q->where('session_id', '!=', $excludeSessionId))
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn)
            ->sum('quantity');

        return $booked + $held;
    }

    /**
     * Dùng bên trong DB transaction để re-check trước khi insert.
     *
     * @throws \Illuminate\Validation\ValidationException nếu ngày không hợp lệ
     */
    public function canBook(int $roomTypeId, string $checkIn, string $checkOut, int $quantity, ?string $excludeSessionId = null): bool
    {
        return $this->check($roomTypeId, $checkIn, $checkOut, $quantity, $excludeSessionId)['can_book'];
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
