<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;

class RoomService
{
    public function list(?int $roomTypeId = null): Collection
    {
        return Room::with('roomType')
            ->when($roomTypeId, fn ($q) => $q->where('room_type_id', $roomTypeId))
            ->orderBy('room_number')
            ->get();
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
