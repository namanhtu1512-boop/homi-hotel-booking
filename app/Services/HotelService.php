<?php

namespace App\Services;

use App\Models\HotelInfo;

/**
 * HotelService — quản lý dữ liệu khách sạn duy nhất (singleton, id = 1).
 * Không có khái niệm create/list/delete nhiều khách sạn.
 */
class HotelService
{
    public function __construct(private readonly ImageService $imageService) {}

    /**
     * Lấy bản ghi khách sạn duy nhất (tạo nếu chưa tồn tại, phòng trường hợp
     * seeder chưa chạy).
     */
    public function singleton(): HotelInfo
    {
        return HotelInfo::with(['images', 'amenities'])->firstOrCreate(
            ['id' => 1],
            ['name' => 'Homi Hotel', 'address' => 'Đang cập nhật', 'is_open' => true]
        );
    }

    public function update(array $data): HotelInfo
    {
        $hotel = $this->singleton();

        $fields = array_filter([
            'name'            => $data['name'] ?? null,
            'address'         => $data['address'] ?? null,
            'description'     => $data['description'] ?? null,
            'hotline'         => $data['hotline'] ?? null,
            'email'           => $data['email'] ?? null,
            'check_in_time'   => $data['check_in_time'] ?? null,
            'check_out_time'  => $data['check_out_time'] ?? null,
            'policies'        => $data['policies'] ?? null,
            'star_rating'     => $data['star_rating'] ?? null,
        ], fn ($v) => $v !== null);

        if (array_key_exists('is_open', $data)) {
            $fields['is_open'] = $data['is_open'];
        }

        $hotel->update($fields);

        if (isset($data['amenity_ids'])) {
            $hotel->amenities()->sync($data['amenity_ids']);
        }

        if (! empty($data['images'])) {
            // replace = true: thay toàn bộ ảnh khi update
            $this->imageService->syncHotelImages($hotel, $data['images'], replace: true);
        }

        return $hotel->fresh(['amenities', 'images']);
    }
}
