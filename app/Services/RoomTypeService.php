<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoomTypeService
{
    public function __construct(private readonly ImageService $imageService) {}

    /**
     * Danh sách loại phòng toàn hệ thống (mọi khách sạn) cho trang quản trị.
     */
    public function adminList(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = RoomType::withTrashed()->with('hotel');

        if (! empty($filters['hotel_id'])) {
            $query->where('hotel_id', $filters['hotel_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function listByHotel(int $hotelId, bool $adminView = false): Collection
    {
        $query = RoomType::where('hotel_id', $hotelId)->with('images');

        if (! $adminView) {
            $query->where('status', 'active');
        }

        return $query->orderBy('price_per_night')->get();
    }

    public function find(int $id): RoomType
    {
        return RoomType::with(['images', 'hotel'])->findOrFail($id);
    }

    public function findPublic(int $id): RoomType
    {
        return RoomType::where('status', 'active')
            ->whereHas('hotel', fn ($q) => $q->where('status', 'active'))
            ->with(['images', 'hotel'])
            ->findOrFail($id);
    }

    public function create(Hotel $hotel, array $data): RoomType
    {
        $this->assertHotelActive($hotel);

        $roomType = $hotel->roomTypes()->create([
            'name'           => $data['name'],
            'slug'           => Str::slug($data['name']),
            'description'    => $data['description'] ?? null,
            'price_per_night' => $data['price_per_night'],
            'capacity'       => $data['capacity'],
            'bed_type'       => $data['bed_type'] ?? null,
            'area'           => $data['area'] ?? null,
            'total_rooms'    => $data['total_rooms'],
            'status'         => 'active',
        ]);

        if (! empty($data['images'])) {
            $this->imageService->syncRoomTypeImages($roomType, $data['images']);
        }

        return $roomType->load('images');
    }

    public function update(RoomType $roomType, array $data): RoomType
    {
        if (isset($data['total_rooms'])) {
            $this->validateInventoryReduction($data['total_rooms']);
        }

        $fields = array_filter([
            'name'           => $data['name'] ?? null,
            'description'    => $data['description'] ?? null,
            'price_per_night' => $data['price_per_night'] ?? null,
            'capacity'       => $data['capacity'] ?? null,
            'bed_type'       => $data['bed_type'] ?? null,
            'area'           => $data['area'] ?? null,
            'total_rooms'    => $data['total_rooms'] ?? null,
        ], fn ($v) => $v !== null);

        if (isset($data['name'])) {
            $fields['slug'] = Str::slug($data['name']);
        }

        $roomType->update($fields);

        if (! empty($data['images'])) {
            $this->imageService->syncRoomTypeImages($roomType, $data['images'], replace: true);
        }

        return $roomType->fresh('images');
    }

    public function updatePrice(RoomType $roomType, float $pricePerNight): RoomType
    {
        $roomType->update(['price_per_night' => $pricePerNight]);

        return $roomType->fresh();
    }

    public function updateInventory(RoomType $roomType, int $totalRooms): RoomType
    {
        $this->validateInventoryReduction($totalRooms);
        $roomType->update(['total_rooms' => $totalRooms]);

        return $roomType->fresh();
    }

    /**
     * Soft delete nếu không có booking đang hoạt động.
     * Nếu có booking active thì chuyển về trạng thái 'hidden'.
     */
    public function softDeleteOrDeactivate(RoomType $roomType): void
    {
        $hasActiveBookings = $roomType->bookingItems()
            ->whereHas('booking', fn ($q) => $q->whereIn('status', ['pending', 'confirmed']))
            ->exists();

        if ($hasActiveBookings) {
            $roomType->update(['status' => 'hidden']);
        } else {
            $roomType->delete();
        }
    }

    public function restore(RoomType $roomType): void
    {
        $roomType->restore();
    }

    public function toggleStatus(RoomType $roomType): RoomType
    {
        $roomType->update([
            'status' => $roomType->status === 'active' ? 'hidden' : 'active',
        ]);

        return $roomType->fresh();
    }

    private function validateInventoryReduction(int $newTotal): void
    {
        if ($newTotal < 1) {
            throw ValidationException::withMessages([
                'total_rooms' => ['Số lượng phòng phải lớn hơn hoặc bằng 1.'],
            ]);
        }
    }

    /**
     * Rule dùng chung: không cho tạo loại phòng mới khi khách sạn đang bị ẩn.
     * Các thao tác sửa/xóa/đổi giá trên phòng đã tồn tại vẫn được phép dù
     * hotel đang ẩn, vì admin/staff có thể cần chỉnh sửa dữ liệu trước khi
     * hiện lại khách sạn.
     */
    private function assertHotelActive(Hotel $hotel): void
    {
        if ($hotel->status !== 'active') {
            throw ValidationException::withMessages([
                'hotel_id' => ['Không thể thêm phòng cho khách sạn đang bị ẩn.'],
            ]);
        }
    }
}
