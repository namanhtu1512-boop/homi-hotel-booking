<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\HotelInfo;
use App\Models\Review;
use App\Models\RoomType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as BaseLengthAwarePaginator;
use Illuminate\Support\Facades\DB;
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
     * Danh sách phòng active với filter cho trang công khai (trang chủ + /rooms).
     * Khi có đủ check_in/check_out, chỉ trả về loại phòng còn đủ số lượng
     * trống trong khoảng ngày đó (dùng lại logic overlap của AvailabilityService,
     * tính bulk 1 query thay vì gọi lặp từng phòng).
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

        if (! empty($filters['bed_type'])) {
            $query->where('bed_type', $filters['bed_type']);
        }

        $this->applySort($query, $filters['sort'] ?? null);

        $hasDateRange = ! empty($filters['check_in']) && ! empty($filters['check_out']);

        if (! $hasDateRange) {
            $paginator = $query->paginate($perPage)->withQueryString();

            $this->attachAvailability($paginator->getCollection(), now()->toDateString(), now()->addDay()->toDateString());

            return $paginator;
        }

        $quantity = max(1, (int) ($filters['quantity'] ?? 1));
        $roomTypes = $query->get();

        $this->attachAvailability($roomTypes, $filters['check_in'], $filters['check_out']);

        $available = $roomTypes->filter(
            fn (RoomType $room) => $room->available_quantity >= $quantity
        )->values();

        $page = (int) request('page', 1);
        $slice = $available->slice(($page - 1) * $perPage, $perPage)->values();

        return (new BaseLengthAwarePaginator($slice, $available->count(), $perPage, $page, [
            'path'  => request()->url(),
            'query' => request()->query(),
        ]));
    }

    /**
     * Gắn `available_quantity` (số phòng còn trống thực tế trong khoảng
     * check_in/check_out cho trước, đã trừ các booking đang giữ chỗ) lên
     * từng RoomType — dùng chung cho cả nhánh có/không có date range trong
     * search(), để danh sách công khai luôn khớp với số liệu admin thấy.
     */
    private function attachAvailability(iterable $roomTypes, string $checkIn, string $checkOut): void
    {
        $roomTypes = collect($roomTypes);

        if ($roomTypes->isEmpty()) {
            return;
        }

        $bookedCounts = DB::table('booking_items')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->whereIn('booking_items.room_type_id', $roomTypes->pluck('id'))
            ->whereIn('bookings.status', BookingStatus::holdingStatuses())
            ->whereDate('bookings.check_in', '<', $checkOut)
            ->whereDate('bookings.check_out', '>', $checkIn)
            ->groupBy('booking_items.room_type_id')
            ->selectRaw('booking_items.room_type_id, SUM(booking_items.quantity) as total_quantity')
            ->pluck('total_quantity', 'room_type_id');

        foreach ($roomTypes as $room) {
            $room->available_quantity = max(0, $room->total_rooms - (int) $bookedCounts->get($room->id, 0));
        }
    }

    private function applySort($query, ?string $sort): void
    {
        match ($sort) {
            'price_desc' => $query->orderByDesc('price_per_night'),
            'newest'     => $query->orderByDesc('created_at'),
            'rating'     => $query->leftJoinSub(
                Review::visible()->selectRaw('room_type_id, AVG(rating) as avg_rating')->groupBy('room_type_id'),
                'review_avg',
                'review_avg.room_type_id',
                '=',
                'room_types.id'
            )->orderByDesc('review_avg.avg_rating')->select('room_types.*'),
            default => $query->orderBy('price_per_night'),
        };
    }

    public function find(int $id): RoomType
    {
        return RoomType::with('images')->findOrFail($id);
    }

    public function featured(int $limit = 6): Collection
    {
        return RoomType::active()->featured()->with('images')->orderBy('price_per_night')->limit($limit)->get();
    }

    /**
     * Danh sách loại phòng cho trang quản lý (admin/staff) kèm
     * `available_today` — số phòng còn trống hôm nay, dùng chung để không
     * lặp lại đoạn tính booked_count ở nhiều controller.
     */
    public function adminIndexWithAvailability(): Collection
    {
        $roomTypes = $this->list(adminView: true);

        $today = now()->toDateString();

        // Alias tường minh cho cột SUM — pluck(DB::raw(...)) không alias sẽ
        // đoán sai tên thuộc tính trên stdClass tùy driver (lỗi thật gặp
        // trên MySQL: "Undefined property: stdClass::$quantity").
        $bookedCounts = DB::table('booking_items')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->whereIn('bookings.status', BookingStatus::holdingStatuses())
            ->where('bookings.check_in', '<=', $today)
            ->where('bookings.check_out', '>', $today)
            ->groupBy('booking_items.room_type_id')
            ->selectRaw('booking_items.room_type_id, SUM(booking_items.quantity) as total_quantity')
            ->pluck('total_quantity', 'room_type_id');

        $roomTypes->each(function (RoomType $room) use ($bookedCounts) {
            $room->available_today = max(0, $room->total_rooms - (int) $bookedCounts->get($room->id, 0));
        });

        return $roomTypes;
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
            'is_featured'     => $data['is_featured'] ?? false,
        ]);

        if (! empty($data['images'])) {
            $this->imageService->syncRoomTypeImages($roomType, $data['images']);
        }

        return $roomType->load('images');
    }

    public function update(RoomType $roomType, array $data): RoomType
    {
        if (isset($data['total_rooms'])) {
            $this->validateInventoryReduction($data['total_rooms'], $roomType);
        }

        // array_intersect_key (không phải array_filter loại bỏ null) — để admin
        // xóa field tùy chọn (description/bed_type/area) về rỗng thì giá trị
        // null vẫn được ghi xuống DB thay vì bị bỏ qua.
        $updatable = ['name', 'description', 'price_per_night', 'capacity', 'bed_type', 'area', 'total_rooms', 'is_featured'];
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
        $this->validateInventoryReduction($totalRooms, $roomType);
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
            ->whereHas('booking', fn ($q) => $q->whereIn('status', BookingStatus::holdingStatuses()))
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

    private function validateInventoryReduction(int $newTotal, ?RoomType $roomType = null): void
    {
        if ($newTotal < 1) {
            throw ValidationException::withMessages([
                'total_rooms' => ['Số lượng phòng phải lớn hơn hoặc bằng 1.'],
            ]);
        }

        if (! $roomType) {
            return;
        }

        $maxBooked = $this->maxFutureBookedQuantity($roomType);

        if ($newTotal < $maxBooked) {
            throw ValidationException::withMessages([
                'total_rooms' => ["Không thể giảm xuống {$newTotal} phòng vì đang có thời điểm giữ chỗ đồng thời {$maxBooked} phòng trong tương lai (đơn pending/confirmed/checked_in)."],
            ]);
        }
    }

    /**
     * Số phòng tối đa bị giữ chỗ cùng lúc kể từ hôm nay trở đi, tính bằng
     * sweep-line trên các mốc check_in/check_out của booking_items đang giữ
     * phòng (pending/confirmed/checked_in) — không chỉ SUM tổng quantity, vì
     * các đơn không nhất thiết giao nhau cùng lúc.
     */
    private function maxFutureBookedQuantity(RoomType $roomType): int
    {
        $today = now()->toDateString();

        $items = $roomType->bookingItems()
            ->whereHas('booking', fn ($q) => $q
                ->whereIn('status', BookingStatus::holdingStatuses())
                ->where('check_out', '>', $today))
            ->with('booking:id,check_in,check_out')
            ->get(['id', 'booking_id', 'quantity']);

        if ($items->isEmpty()) {
            return 0;
        }

        $events = [];
        foreach ($items as $item) {
            $checkIn  = max($item->booking->check_in->toDateString(), $today);
            $checkOut = $item->booking->check_out->toDateString();
            $events[] = [$checkIn, $item->quantity];
            $events[] = [$checkOut, -$item->quantity];
        }

        usort($events, fn ($a, $b) => $a[0] <=> $b[0]);

        $running = 0;
        $max     = 0;
        foreach ($events as [, $delta]) {
            $running += $delta;
            $max = max($max, $running);
        }

        return $max;
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
