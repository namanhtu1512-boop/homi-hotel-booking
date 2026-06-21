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

        // array_key_exists (không phải array_filter loại bỏ null) — để admin
        // xóa một field tùy chọn về rỗng (vd: bỏ chọn star_rating) thì giá trị
        // null vẫn phải được ghi xuống DB thay vì bị bỏ qua.
        $updatable = [
            'name', 'address', 'description', 'hotline', 'email',
            'check_in_time', 'check_out_time', 'policies', 'star_rating', 'is_open',
        ];

        $fields = array_intersect_key($data, array_flip($updatable));

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
