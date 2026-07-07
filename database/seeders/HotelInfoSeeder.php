<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\HotelInfo;
use Illuminate\Database\Seeder;

/**
 * Seed dữ liệu cho khách sạn singleton Homi (1 bản ghi hotel_info duy
 * nhất) và danh mục tiện ích dùng chung (hotel + room type). Vì hệ thống
 * chỉ vận hành 1 khách sạn, không seed nhiều bản ghi hotel.
 *
 * Loại phòng được seed riêng ở RoomTypeSeeder — không lặp lại ở đây để
 * tránh 2 nguồn dữ liệu phòng xung đột nhau (đã từng xảy ra: seeder này
 * tạo phòng trước với giá/mô tả sơ sài, RoomTypeSeeder chạy sau chỉ bổ
 * sung được ảnh vì slug đã tồn tại, còn giá/mô tả đầy đủ hơn thì bị bỏ qua).
 */
class HotelInfoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAmenities();
        $this->seedHotelInfo();
    }

    private function seedAmenities(): void
    {
        $amenities = [
            ['name' => 'Wifi miễn phí',       'icon' => 'wifi'],
            ['name' => 'Bãi đỗ xe',            'icon' => 'parking'],
            ['name' => 'Hồ bơi',               'icon' => 'pool'],
            ['name' => 'Phòng gym',             'icon' => 'gym'],
            ['name' => 'Nhà hàng',              'icon' => 'restaurant'],
            ['name' => 'Quầy bar',              'icon' => 'bar'],
            ['name' => 'Spa',                   'icon' => 'spa'],
            ['name' => 'Dịch vụ phòng 24/7',   'icon' => 'room-service'],
            ['name' => 'Điều hòa',              'icon' => 'ac'],
            ['name' => 'Thang máy',             'icon' => 'elevator'],
        ];

        foreach ($amenities as $amenity) {
            Amenity::firstOrCreate(['name' => $amenity['name']], $amenity);
        }
    }

    private function seedHotelInfo(): void
    {
        $hotel = HotelInfo::instance();

        $hotel->update([
            'name'           => 'Homi Hotel',
            'address'        => '123 Bạch Đằng, Hải Châu, Đà Nẵng',
            'description'    => 'Khách sạn Homi tọa lạc tại trung tâm thành phố, gần sông Hàn, phù hợp cho cả du lịch và công tác. Không gian hiện đại, dịch vụ tận tâm, đầy đủ tiện nghi cho mọi nhu cầu lưu trú.',
            'check_in_time'  => '14:00',
            'check_out_time' => '12:00',
            'policies'       => "Không hút thuốc trong phòng.\nMang theo CMND/CCCD hoặc hộ chiếu khi nhận phòng.\nHủy đơn miễn phí trước 24 giờ nhận phòng.",
            'star_rating'    => 4,
            'status'         => 'active',
        ]);

        $hotel->amenities()->sync(
            Amenity::whereIn('name', [
                'Wifi miễn phí', 'Bãi đỗ xe', 'Hồ bơi', 'Phòng gym',
                'Nhà hàng', 'Quầy bar', 'Spa', 'Dịch vụ phòng 24/7',
                'Điều hòa', 'Thang máy',
            ])->pluck('id')
        );
    }
}
