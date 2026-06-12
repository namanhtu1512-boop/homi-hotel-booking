<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class RoomTypeService
{
    public function listByHotel(int $hotelId, bool $adminView = false): \Illuminate\Database\Eloquent\Collection
    {
        $query = RoomType::where('hotel_id', $hotelId)->with('images');

        if (! $adminView) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    public function find(int $id): RoomType
    {
        return RoomType::with(['images', 'hotel'])->findOrFail($id);
    }

    public function findPublic(int $id): RoomType
    {
        return RoomType::where('is_active', true)
            ->whereHas('hotel', fn($q) => $q->where('is_active', true))
            ->with(['images', 'hotel'])
            ->findOrFail($id);
    }

    public function create(Hotel $hotel, array $data): RoomType
    {
        if (! $hotel->is_active) {
            throw ValidationException::withMessages([
                'hotel_id' => ['Không thể thêm phòng cho khách sạn đang bị ẩn.'],
            ]);
        }

        $roomType = $hotel->roomTypes()->create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'capacity'    => $data['capacity'],
            'bed_type'    => $data['bed_type'] ?? null,
            'area_sqm'    => $data['area_sqm'] ?? null,
            'base_price'  => $data['base_price'],
            'weekend_price' => $data['weekend_price'] ?? null,
            'total_rooms' => $data['total_rooms'],
            'is_active'   => true,
        ]);

        if (! empty($data['images'])) {
            foreach ($data['images'] as $index => $path) {
                $roomType->images()->create(['image_path' => $path, 'sort_order' => $index]);
            }
        }

        return $roomType->load('images');
    }

    public function update(RoomType $roomType, array $data): RoomType
    {
        if (isset($data['total_rooms'])) {
            $this->validateInventoryReduction($roomType, $data['total_rooms']);
        }

        $roomType->update(array_filter([
            'name'          => $data['name'] ?? null,
            'description'   => $data['description'] ?? null,
            'capacity'      => $data['capacity'] ?? null,
            'bed_type'      => $data['bed_type'] ?? null,
            'area_sqm'      => $data['area_sqm'] ?? null,
            'base_price'    => $data['base_price'] ?? null,
            'weekend_price' => $data['weekend_price'] ?? null,
            'total_rooms'   => $data['total_rooms'] ?? null,
        ], fn($v) => ! is_null($v)));

        return $roomType->fresh('images');
    }

    public function updatePrice(RoomType $roomType, float $basePrice, ?float $weekendPrice = null): RoomType
    {
        $roomType->update(array_filter([
            'base_price'    => $basePrice,
            'weekend_price' => $weekendPrice,
        ], fn($v) => ! is_null($v)));

        return $roomType->fresh();
    }

    public function updateInventory(RoomType $roomType, int $totalRooms): RoomType
    {
        $roomType->update(['total_rooms' => $totalRooms]);

        return $roomType->fresh();
    }

    public function softDeleteOrDeactivate(RoomType $roomType): void
    {
        $hasActiveBookings = $roomType->bookingItems()
            ->whereHas('booking', fn($q) => $q->whereIn('status', ['pending', 'confirmed']))
            ->exists();

        if ($hasActiveBookings) {
            $roomType->update(['is_active' => false]);
        } else {
            $roomType->delete();
        }
    }

    private function validateInventoryReduction(RoomType $roomType, int $newTotal): void
    {
        if ($newTotal < 1) {
            throw ValidationException::withMessages([
                'total_rooms' => ['Số lượng phòng phải lớn hơn hoặc bằng 1.'],
            ]);
        }
    }
}
