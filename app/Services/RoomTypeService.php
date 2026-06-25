<?php

namespace App\Services;

use App\Models\HotelInfo;
use App\Models\RoomType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoomTypeService
{
    public function __construct(
        private readonly ImageService $imageService,
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
     * BE2/BE3 Tuần 7 — Danh sách phòng active với filter cho trang công khai.
     * check_in/check_out chỉ validate ở tầng Request, chưa lọc availability
     * (chức năng đó thuộc Tuần 9).
     */
    public function search(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = RoomType::with('images')->where('status', 'active');

        if (! empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$kw}%")
                ->orWhere('description', 'like', "%{$kw}%")
            );
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== null) {
            $query->where('price_per_night', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== null) {
            $query->where('price_per_night', '<=', $filters['max_price']);
        }

        if (! empty($filters['capacity'])) {
            $query->where('capacity', '>=', $filters['capacity']);
        }

        return $query->orderBy('price_per_night')->paginate($perPage)->withQueryString();
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
        return RoomType::where('status', 'active')
            ->with('images')
            ->findOrFail($id);
    }

    public function create(array $data): RoomType
    {
        $this->assertHotelOperational();

        $roomType = RoomType::create([
            'name'           => $data['name'],
            'slug'           => $this->uniqueSlug($data['name']),
            'description'    => $data['description'] ?? null,
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
     * Rule dùng chung: không cho tạo loại phòng mới khi khách sạn đang bảo trì.
     * Các thao tác sửa/xóa/đổi giá trên phòng đã tồn tại vẫn được phép dù
     * khách sạn đang bảo trì, vì admin/staff có thể cần chỉnh sửa dữ liệu
     * trước khi hoạt động trở lại.
     */
    private function assertHotelOperational(): void
    {
        if (HotelInfo::instance()->status !== 'active') {
            throw ValidationException::withMessages([
                'status' => ['Không thể thêm loại phòng khi khách sạn đang bảo trì.'],
            ]);
        }
    }
}
