<?php

namespace App\Services;

use App\Models\RoomType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoomTypeService
{
    public function __construct(
        private readonly ImageService $imageService,
        private readonly HotelService $hotelService,
    ) {}

    public function list(bool $adminView = false): Collection
    {
        $query = RoomType::with('images');

        if (! $adminView) {
            $query->where('status', 'active');
        }

        return $query->orderBy('price_per_night')->get();
    }

    /**
     * Danh sách phòng active phục vụ trang public /rooms, lọc theo dữ liệu
     * đã validate từ FilterRoomRequest. Không lọc theo 'amenities' vì
     * room_types hiện chưa có quan hệ tới amenities (chỉ hotel_info có).
     */
    public function search(array $filters): Collection
    {
        $query = RoomType::active()->with('images');

        if (! empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        if (isset($filters['min_price'])) {
            $query->where('price_per_night', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price_per_night', '<=', $filters['max_price']);
        }

        if (isset($filters['capacity'])) {
            $query->where('capacity', '>=', $filters['capacity']);
        }

        return $query->orderBy('price_per_night')->get();
    }

    public function find(int $id): RoomType
    {
        return RoomType::with('images')->findOrFail($id);
    }

    /**
     * Lấy 1 phòng active cho trang public /rooms/{id} — 404 nếu phòng không
     * tồn tại, đang ẩn, bảo trì hoặc đã bị xóa mềm.
     */
    public function findActive(int $id): RoomType
    {
        return RoomType::active()->with('images')->findOrFail($id);
    }

    public function create(array $data): RoomType
    {
        $this->assertHotelOpen();

        $roomType = RoomType::create([
            'name'            => $data['name'],
            'slug'            => $this->uniqueSlug($data['name']),
            'description'     => $data['description'] ?? null,
            'price_per_night' => $data['price_per_night'],
            'capacity'        => $data['capacity'],
            'bed_type'        => $data['bed_type'] ?? null,
            'area'            => $data['area'] ?? null,
            'total_rooms'     => $data['total_rooms'],
            'status'          => 'active',
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

        // array_intersect_key (không phải array_filter loại bỏ null) — để admin
        // xóa field tùy chọn (description/bed_type/area) về rỗng thì giá trị
        // null vẫn được ghi xuống DB thay vì bị bỏ qua.
        $updatable = ['name', 'description', 'price_per_night', 'capacity', 'bed_type', 'area', 'total_rooms'];
        $fields = array_intersect_key($data, array_flip($updatable));

        if (isset($data['name'])) {
            $fields['slug'] = $this->uniqueSlug($data['name'], $roomType->id);
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

    public function toggleStatus(RoomType $roomType): RoomType
    {
        $roomType->update([
            'status' => $roomType->status === 'active' ? 'hidden' : 'active',
        ]);

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

    /**
     * Sinh slug duy nhất cho loại phòng (slug giờ unique toàn cục vì chỉ còn 1 khách sạn).
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (
            RoomType::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
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
     * Rule dùng chung: không cho tạo loại phòng mới khi khách sạn đang đóng
     * (is_open = false, vd: đang bảo trì toàn bộ). Sửa/xóa/đổi giá trên phòng
     * đã tồn tại vẫn được phép dù khách sạn đang đóng.
     */
    private function assertHotelOpen(): void
    {
        if (! $this->hotelService->singleton()->is_open) {
            throw ValidationException::withMessages([
                'hotel' => ['Không thể thêm phòng khi khách sạn đang đóng.'],
            ]);
        }
    }
}
