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
     * @param bool $lock Khóa hàng room_type (SELECT ... FOR UPDATE) — bắt buộc khi
     *   gọi bên trong transaction tạo booking, để 2 request đặt cùng room_type cùng
     *   lúc không thể cùng đọc "còn chỗ" rồi cùng ghi thành công (race condition).
     *
     * @throws \Illuminate\Validation\ValidationException nếu ngày không hợp lệ
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException nếu room_type không tồn tại/không active
     */
    public function check(int $roomTypeId, string $checkIn, string $checkOut, int $quantity = 1, bool $lock = false): array
    {
        $this->dateRange->validate($checkIn, $checkOut);

        $query = RoomType::where('status', 'active');

        if ($lock) {
            $query->lockForUpdate();
        }

        $roomType = $query->findOrFail($roomTypeId);

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
     *
     * Dùng whereDate() thay vì where() để so sánh: cột `check_in`/`check_out` là
     * cast 'date' nhưng Eloquent vẫn ghi xuống DB theo $dateFormat đầy đủ
     * (Y-m-d H:i:s) — MySQL tự cắt phần giờ vì kiểu cột DATE, nhưng SQLite (dùng
     * khi chạy test) không ép kiểu nên có thể lưu kèm "00:00:00". whereDate()
     * chuẩn hóa cả hai vế về phần ngày, tránh sai lệch giữa 2 loại DB.
     */
    public function getBookedQuantity(int $roomTypeId, string $checkIn, string $checkOut): int
    {
        return (int) BookingItem::where('room_type_id', $roomTypeId)
            ->whereHas('booking', fn ($q) => $q
                ->whereIn('status', BookingStatus::holdingStatuses())
                ->whereDate('check_in', '<', $checkOut)
                ->whereDate('check_out', '>', $checkIn)
            )
            ->sum('quantity');
    }

    /**
     * Dùng bên trong DB transaction để re-check trước khi insert.
     *
     * Khóa hàng room_type (FOR UPDATE) trong lúc kiểm tra: nếu 2 khách đặt cùng
     * room_type cùng lúc, transaction thứ hai phải đợi transaction thứ nhất
     * commit (hoặc rollback) rồi mới được đọc số lượng đã đặt — nên không thể
     * cả hai cùng thấy "còn chỗ" và cùng tạo booking vượt total_rooms.
     *
     * @throws \Illuminate\Validation\ValidationException nếu ngày không hợp lệ
     */
    public function canBook(int $roomTypeId, string $checkIn, string $checkOut, int $quantity): bool
    {
        return $this->check($roomTypeId, $checkIn, $checkOut, $quantity, lock: true)['can_book'];
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
