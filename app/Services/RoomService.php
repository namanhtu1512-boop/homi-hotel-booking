<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;

class RoomService
{
    /**
     * Danh sách phòng vật lý cho trang "Phòng vật lý" (admin/staff) — kèm
     * thuộc tính runtime `occupancy_status` (không phải cột DB) cho cột
     * "Nhận / trả phòng": trạng thái lưu trú hôm nay + cảnh báo quá giờ trả
     * phòng, tính real-time từ Booking::isOverdueCheckout() mỗi lần gọi —
     * không phụ thuộc job quét nền (xem BookingService::flagOverdueCheckouts()).
     */
    public function list(?int $roomTypeId = null): Collection
    {
        $rooms = Room::with(['roomType', 'activeStay.bookingItem.booking', 'lastStay'])
            ->when($roomTypeId, fn ($q) => $q->where('room_type_id', $roomTypeId))
            ->orderBy('room_number')
            ->get();

        $rooms->each(fn (Room $room) => $room->occupancy_status = $this->occupancyStatus($room));

        return $rooms;
    }

    /**
     * @return array{state: string, booking: ?\App\Models\Booking, since: ?\Carbon\Carbon, checked_out_at: ?\Carbon\Carbon}
     */
    private function occupancyStatus(Room $room): array
    {
        if ($room->activeStay) {
            $booking = $room->activeStay->bookingItem->booking;

            return [
                'state'          => $booking->isOverdueCheckout() ? 'overdue' : 'occupied',
                'booking'        => $booking,
                'since'          => $room->activeStay->checked_in_at,
                'checked_out_at' => null,
            ];
        }

        if ($room->lastStay?->checked_out_at?->timezone('Asia/Ho_Chi_Minh')->isToday()) {
            return [
                'state'          => 'checked_out_today',
                'booking'        => null,
                'since'          => null,
                'checked_out_at' => $room->lastStay->checked_out_at,
            ];
        }

        return ['state' => 'empty', 'booking' => null, 'since' => null, 'checked_out_at' => null];
    }

    public function find(int $id): Room
    {
        return Room::findOrFail($id);
    }

    public function create(array $data): Room
    {
        return Room::create($data);
    }

    public function update(Room $room, array $data): Room
    {
        $room->update($data);

        return $room->fresh();
    }

    public function delete(Room $room): void
    {
        $room->delete();
    }

    public function updateHousekeepingStatus(Room $room, string $status): Room
    {
        $room->update(['housekeeping_status' => $status]);

        return $room->fresh();
    }

    /**
     * Phòng vật lý thuộc room_type này và hiện không có khách (không bị
     * chiếm bởi 1 đơn đang CHECKED_IN) — dùng để chọn khi check-in.
     */
    public function availableForRoomType(int $roomTypeId): Collection
    {
        return Room::where('room_type_id', $roomTypeId)
            ->orderBy('room_number')
            ->get()
            ->reject(fn (Room $room) => $room->isOccupied())
            ->values();
    }
}
