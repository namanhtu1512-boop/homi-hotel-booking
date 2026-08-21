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
     *
     * Chỉ phân trang 1 collection đã lọc/gắn availability sẵn (searchCandidates())
     * — không paginate() thẳng ở DB nữa, vì nhánh có date-range luôn phải lọc
     * available_quantity trong bộ nhớ nên trước đây phải duy trì 2 cách phân
     * trang khác nhau. Danh mục phòng của 1 khách sạn nhỏ nên load hết vào
     * Collection rồi tự phân trang không phải là vấn đề hiệu năng.
     */
    public function search(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return $this->paginate($this->searchCandidates($filters), $perPage);
    }

    /**
     * Phân trang thủ công 1 Collection đã lọc/sắp xếp sẵn — tách riêng để
     * RoomController có thể tự sắp lại thứ tự candidates (sort best_match
     * theo kết quả RoomCombinationService) rồi mới phân trang, thay vì phải
     * lặp lại đoạn slice/LengthAwarePaginator này.
     */
    public function paginate(Collection $items, int $perPage = 12): LengthAwarePaginator
    {
        $page  = (int) request('page', 1);
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new BaseLengthAwarePaginator($slice, $items->count(), $perPage, $page, [
            'path'  => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /**
     * Lọc + gắn `available_quantity` + sắp xếp, KHÔNG phân trang — dùng chung
     * bởi search() (trang danh sách) và RoomController/HomeController khi cần
     * đưa thẳng tập ứng viên vào RoomCombinationService.
     *
     * Ngữ nghĩa lọc theo `quantity` phụ thuộc `guests` có được truyền hay
     * không:
     *   - Không có `guests`: hành vi cũ — 1 loại phòng phải TỰ đủ `quantity`
     *     phòng (available_quantity >= quantity).
     *   - Có `guests`: nới lỏng thành "còn ít nhất 1 phòng trống" — loại
     *     phòng chỉ cần đủ điều kiện để CÓ THỂ góp vào 1 tổ hợp nhiều loại;
     *     việc tổ hợp đó có thực sự đủ quantity+guests hay không do
     *     RoomCombinationService quyết định, không lọc cứng ở đây.
     */
    public function searchCandidates(array $filters = []): Collection
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

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['amenities'])) {
            foreach ($filters['amenities'] as $amenityId) {
                $query->whereHas('amenities', fn ($q) => $q->where('amenities.id', $amenityId));
            }
        }

        $this->applySort($query, $filters['sort'] ?? null);

        $hasDateRange = ! empty($filters['check_in']) && ! empty($filters['check_out']);
        $checkIn  = $hasDateRange ? $filters['check_in']  : now()->toDateString();
        $checkOut = $hasDateRange ? $filters['check_out'] : now()->addDay()->toDateString();

        $roomTypes = $query->get();

        $this->attachAvailability($roomTypes, $checkIn, $checkOut);

        if (! $hasDateRange) {
            return $roomTypes;
        }

        $quantity = max(1, (int) ($filters['quantity'] ?? 1));
        $hasGuestsFilter = ! empty($filters['guests']);

        return $roomTypes->filter(
            fn (RoomType $room) => $hasGuestsFilter
                ? $room->available_quantity > 0
                : $room->available_quantity >= $quantity
        )->values();
    }

    /**
     * Gắn `available_quantity` (số phòng còn trống thực tế trong khoảng
     * check_in/check_out cho trước, đã trừ cả booking đang giữ chỗ VÀ
     * RoomHold đang active — cùng logic với AvailabilityService::getBookedQuantity(),
     * tránh trường hợp trang danh sách công khai báo còn phòng trong khi
     * phòng đó thực ra đang bị 1 session khác tạm giữ 15 phút) lên từng
     * RoomType — dùng chung cho cả nhánh có/không có date range trong
     * search(), để danh sách công khai luôn khớp với số liệu admin thấy.
     */
    private function attachAvailability(iterable $roomTypes, string $checkIn, string $checkOut): void
    {
        $roomTypes = collect($roomTypes);

        if ($roomTypes->isEmpty()) {
            return;
        }

        $roomTypeIds = $roomTypes->pluck('id');

        $bookedCounts = DB::table('booking_items')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->whereIn('booking_items.room_type_id', $roomTypeIds)
            ->whereIn('bookings.status', BookingStatus::holdingStatuses())
            ->whereDate('bookings.check_in', '<', $checkOut)
            ->whereDate('bookings.check_out', '>', $checkIn)
            ->groupBy('booking_items.room_type_id')
            ->selectRaw('booking_items.room_type_id, SUM(booking_items.quantity) as total_quantity')
            ->pluck('total_quantity', 'room_type_id');

        $heldCounts = DB::table('room_holds')
            ->whereIn('room_type_id', $roomTypeIds)
            ->where('expires_at', '>', now())
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn)
            ->groupBy('room_type_id')
            ->selectRaw('room_type_id, SUM(quantity) as total_quantity')
            ->pluck('total_quantity', 'room_type_id');

        foreach ($roomTypes as $room) {
            $booked = (int) $bookedCounts->get($room->id, 0) + (int) $heldCounts->get($room->id, 0);
            $room->available_quantity = max(0, $room->total_rooms - $booked);
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
            'popularity' => $query->leftJoinSub(
                DB::table('booking_items')
                    ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
                    ->where('bookings.status', '!=', BookingStatus::CANCELLED->value)
                    ->groupBy('booking_items.room_type_id')
                    ->selectRaw('booking_items.room_type_id, SUM(booking_items.quantity) as total_booked'),
                'popularity_stats',
                'popularity_stats.room_type_id',
                '=',
                'room_types.id'
            )->orderByDesc('popularity_stats.total_booked')->select('room_types.*'),
            // best_match: chỉ có ý nghĩa khi có kết quả RoomCombinationService —
            // controller tự sắp lại Collection sau khi có tổ hợp, ở đây coi như
            // price_asc mặc định (áp dụng khi chưa/không có guests+quantity).
            default => $query->orderBy('price_per_night'),
        };
    }

    public function find(int $id): RoomType
    {
        return RoomType::with(['images', 'amenities'])->findOrFail($id);
    }

    public function featured(int $limit = 6): Collection
    {
        return RoomType::active()->featured()->with('images')->orderBy('price_per_night')->limit($limit)->get();
    }

    /**
     * Danh sách loại phòng cho trang quản lý (admin/staff) kèm
     * `available_today` — số phòng còn trống hôm nay, dùng chung để không
     * lặp lại đoạn tính booked_count ở nhiều controller.
     *
     * $rangeCheckIn/$rangeCheckOut (optional): khi truyền đủ cả 2, gắn thêm
     * `available_range` lên mỗi RoomType — số phòng còn trống thực tế trong
     * khoảng ngày đó (khác `available_today` vốn luôn cố định theo hôm nay,
     * không đổi hành vi cũ khi không truyền tham số).
     */
    public function adminIndexWithAvailability(?string $rangeCheckIn = null, ?string $rangeCheckOut = null): Collection
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

        $rangeBookedCounts = $rangeCheckIn && $rangeCheckOut
            ? $this->bookedQuantitiesInRange($rangeCheckIn, $rangeCheckOut)
            : null;

        $roomTypes->each(function (RoomType $room) use ($bookedCounts, $rangeBookedCounts) {
            $room->available_today = max(0, $room->total_rooms - (int) $bookedCounts->get($room->id, 0));

            if ($rangeBookedCounts !== null) {
                $room->available_range = max(0, $room->total_rooms - (int) $rangeBookedCounts->get($room->id, 0));
            }
        });

        return $roomTypes;
    }

    /**
     * Tổng số phòng đang bị giữ chỗ (SUM quantity) theo từng loại phòng,
     * giao nhau với khoảng [checkIn, checkOut) — overlap chuẩn: check_in <
     * checkOut AND check_out > checkIn. Dùng whereDate() thay vì where() vì
     * lý do tương tự AvailabilityService::getBookedQuantity(): MySQL tự cắt
     * giờ trên cột DATE nhưng SQLite (chạy test) thì không.
     */
    private function bookedQuantitiesInRange(string $checkIn, string $checkOut): \Illuminate\Support\Collection
    {
        return DB::table('booking_items')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->whereIn('bookings.status', BookingStatus::holdingStatuses())
            ->whereDate('bookings.check_in', '<', $checkOut)
            ->whereDate('bookings.check_out', '>', $checkIn)
            ->groupBy('booking_items.room_type_id')
            ->selectRaw('booking_items.room_type_id, SUM(booking_items.quantity) as total_quantity')
            ->pluck('total_quantity', 'room_type_id');
    }

    /**
     * Lấy 1 phòng active cho trang public /rooms/{id} — 404 nếu phòng không
     * tồn tại, đang ẩn, bảo trì hoặc đã bị xóa mềm.
     */
    public function findActive(int $id): RoomType
    {
        return RoomType::where('status', 'active')
            ->with(['images', 'amenities'])
            ->findOrFail($id);
    }

    /**
     * Gắn "độ hiếm" cho từng tiện nghi của $roomType, dựa trên số loại phòng
     * active khác cũng sở hữu tiện nghi đó — không hard-code theo tên phòng,
     * nên vẫn đúng nếu dữ liệu tiện nghi thay đổi sau này:
     *   - exclusive: chỉ mình phòng này có
     *   - premium:   một số phòng có, không phải tất cả
     *   - standard:  phòng nào cũng có (tiện nghi cơ bản)
     * Trả về collection ['amenity' => Amenity, 'tier' => string], tier hiếm
     * nhất xếp lên đầu.
     */
    public function amenityTiers(RoomType $roomType): \Illuminate\Support\Collection
    {
        $totalActive = RoomType::active()->count();

        $roomCounts = DB::table('room_type_amenity')
            ->join('room_types', 'room_types.id', '=', 'room_type_amenity.room_type_id')
            ->where('room_types.status', 'active')
            ->groupBy('amenity_id')
            ->selectRaw('amenity_id, COUNT(DISTINCT room_type_amenity.room_type_id) as room_count')
            ->pluck('room_count', 'amenity_id');

        $tierOrder = ['exclusive' => 0, 'premium' => 1, 'standard' => 2];

        return $roomType->amenities
            ->map(function ($amenity) use ($roomCounts, $totalActive) {
                $count = (int) ($roomCounts[$amenity->id] ?? 1);

                $tier = match (true) {
                    $count <= 1 => 'exclusive',
                    $count < $totalActive => 'premium',
                    default => 'standard',
                };

                return ['amenity' => $amenity, 'tier' => $tier];
            })
            ->sortBy(fn ($row) => $tierOrder[$row['tier']])
            ->values();
    }

    public function create(array $data): RoomType
    {
        $this->assertHotelOperational();

        $roomType = RoomType::create([
            'name'           => $data['name'],
            'slug'           => $this->uniqueSlug($data['name']),
            'category'       => $data['category'] ?? null,
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
        $updatable = ['name', 'category', 'description', 'price_per_night', 'capacity', 'bed_type', 'area', 'total_rooms', 'is_featured'];
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
     * Danh sách loại phòng đã bị xóa mềm, mới xóa gần nhất lên trước — dùng
     * cho khu vực "Đã xóa" ở trang quản lý, nơi admin bấm khôi phục.
     */
    public function trashed(): Collection
    {
        return RoomType::onlyTrashed()->orderByDesc('deleted_at')->get();
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
