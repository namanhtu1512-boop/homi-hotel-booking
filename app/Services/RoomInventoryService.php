<?php

namespace App\Services;

use App\Models\RoomType;
use Illuminate\Validation\ValidationException;

/**
 * Đọc dữ liệu room inventory (số lượng phòng, giá, trạng thái) phục vụ
 * AvailabilityService (tuần 9) và PricingService (tuần 10).
 *
 * Chỉ coi là "khả dụng" khi: room_type active, hotel active, chưa bị
 * xóa mềm và total_rooms hợp lệ (>= 1). SoftDeletes tự loại các bản ghi
 * đã xóa mềm khỏi truy vấn mặc định nên không cần xử lý thêm.
 */
class RoomInventoryService
{
    /**
     * @throws ValidationException nếu loại phòng không tồn tại hoặc không khả dụng để đặt
     */
    public function getBookableRoomType(int $roomTypeId): RoomType
    {
        $roomType = RoomType::where('status', 'active')
            ->whereHas('hotel', fn ($q) => $q->where('status', 'active'))
            ->find($roomTypeId);

        if (! $roomType) {
            throw ValidationException::withMessages([
                'room_type_id' => ['Loại phòng không tồn tại hoặc hiện không khả dụng để đặt.'],
            ]);
        }

        $this->assertValidTotalRooms($roomType);

        return $roomType;
    }

    /**
     * Dữ liệu rút gọn cho availability/pricing: base_price, total_rooms, capacity.
     *
     * @throws ValidationException nếu loại phòng không khả dụng
     */
    public function getPricingData(int $roomTypeId): array
    {
        $roomType = $this->getBookableRoomType($roomTypeId);

        return [
            'room_type_id' => $roomType->id,
            'hotel_id'     => $roomType->hotel_id,
            'base_price'   => (float) $roomType->price_per_night,
            'total_rooms'  => $roomType->total_rooms,
            'capacity'     => $roomType->capacity,
        ];
    }

    /**
     * Tổng số phòng active của một khách sạn (phòng inactive/xóa mềm không tính).
     */
    public function getTotalRoomsByHotel(int $hotelId): int
    {
        return (int) RoomType::where('hotel_id', $hotelId)
            ->where('status', 'active')
            ->sum('total_rooms');
    }

    /**
     * @throws ValidationException nếu total_rooms không hợp lệ (< 1)
     */
    public function assertValidTotalRooms(RoomType $roomType): void
    {
        if ($roomType->total_rooms < 1) {
            throw ValidationException::withMessages([
                'total_rooms' => ['Loại phòng không có phòng khả dụng (tổng số phòng không hợp lệ).'],
            ]);
        }
    }
}
