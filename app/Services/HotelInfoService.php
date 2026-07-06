<?php

namespace App\Services;

use App\Models\HotelInfo;

/**
 * HotelInfoService — quản lý bản ghi khách sạn singleton (xem [[HotelInfo]]).
 * Không có list/create/delete/restore vì hệ thống chỉ có đúng 1 khách sạn.
 *
 * Service này được bind singleton trong container (AppServiceProvider) nên
 * $cached chỉ tồn tại trong phạm vi 1 request/1 test — không dùng static
 * property trên Model vì static property sống suốt cả tiến trình PHP và có
 * thể rò rỉ dữ liệu cũ giữa các test chạy chung 1 process.
 */
class HotelInfoService
{
    private ?HotelInfo $cached = null;

    public function __construct(private readonly ImageService $imageService) {}

    public function get(): HotelInfo
    {
        return $this->cached = HotelInfo::instance()->load(['images', 'amenities']);
    }

    /**
     * Bản ghi khách sạn không kèm eager-load ảnh/tiện ích — dùng cho các trang
     * chỉ cần tên/địa chỉ/số sao (vd sidebar lọc phòng, footer) để tránh query thừa.
     */
    public function current(): HotelInfo
    {
        return $this->cached ??= HotelInfo::instance();
    }

    public function update(array $data): HotelInfo
    {
        $hotel = HotelInfo::instance();

        $fields = array_filter([
            'name'           => $data['name'] ?? null,
            'address'        => $data['address'] ?? null,
            'latitude'       => $data['latitude'] ?? null,
            'longitude'      => $data['longitude'] ?? null,
            'phone'          => $data['phone'] ?? null,
            'email'          => $data['email'] ?? null,
            'description'    => $data['description'] ?? null,
            'check_in_time'  => $data['check_in_time'] ?? null,
            'check_out_time' => $data['check_out_time'] ?? null,
            'policies'       => $data['policies'] ?? null,
            'star_rating'    => $data['star_rating'] ?? null,
            'weekend_surcharge_percent' => $data['weekend_surcharge_percent'] ?? null,
            'child_surcharge_per_night' => $data['child_surcharge_per_night'] ?? null,
        ], fn ($v) => $v !== null);

        $hotel->update($fields);

        if (isset($data['amenity_ids'])) {
            $hotel->amenities()->sync($data['amenity_ids']);
        }

        if (! empty($data['images'])) {
            // replace = true: thay toàn bộ ảnh khi update
            $this->imageService->syncHotelInfoImages($hotel, $data['images'], replace: true);
        }

        $this->cached = $hotel->fresh(['amenities', 'images']);

        return $this->cached;
    }

    /**
     * Đổi trạng thái khách sạn giữa active và maintenance (bảo trì).
     * Khi đang maintenance, không cho tạo loại phòng mới (xem
     * RoomTypeService::assertHotelOperational()).
     */
    public function toggleMaintenance(): HotelInfo
    {
        $hotel = HotelInfo::instance();

        $hotel->update([
            'status' => $hotel->status === 'active' ? 'maintenance' : 'active',
        ]);

        $this->cached = $hotel->fresh();

        return $this->cached;
    }
}
