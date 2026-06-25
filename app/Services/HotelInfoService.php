<?php

namespace App\Services;

use App\Models\HotelInfo;

/**
 * HotelInfoService — quản lý bản ghi khách sạn singleton (xem [[HotelInfo]]).
 * Không có list/create/delete/restore vì hệ thống chỉ có đúng 1 khách sạn.
 */
class HotelInfoService
{
    public function __construct(private readonly ImageService $imageService) {}

    public function get(): HotelInfo
    {
        return HotelInfo::instance()->load(['images', 'amenities']);
    }

    public function update(array $data): HotelInfo
    {
        $hotel = HotelInfo::instance();

        $fields = array_filter([
            'name'           => $data['name'] ?? null,
            'address'        => $data['address'] ?? null,
            'description'    => $data['description'] ?? null,
            'check_in_time'  => $data['check_in_time'] ?? null,
            'check_out_time' => $data['check_out_time'] ?? null,
            'policies'       => $data['policies'] ?? null,
            'star_rating'    => $data['star_rating'] ?? null,
        ], fn ($v) => $v !== null);

        $hotel->update($fields);

        if (isset($data['amenity_ids'])) {
            $hotel->amenities()->sync($data['amenity_ids']);
        }

        if (! empty($data['images'])) {
            // replace = true: thay toàn bộ ảnh khi update
            $this->imageService->syncHotelInfoImages($hotel, $data['images'], replace: true);
        }

        return $hotel->fresh(['amenities', 'images']);
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

        return $hotel->fresh();
    }
}
