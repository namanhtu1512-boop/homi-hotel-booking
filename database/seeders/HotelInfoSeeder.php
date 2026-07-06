<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\HotelInfo;
use App\Services\ImageService;
use Illuminate\Database\Seeder;

/**
 * Seed dữ liệu cho khách sạn singleton Homi (1 bản ghi hotel_info duy
 * nhất) cùng tiện ích và ảnh khách sạn. Vì hệ thống chỉ vận hành 1 khách
 * sạn, không seed nhiều bản ghi hotel — chỉ seed đúng 1 hotel_info.
 *
 * Loại phòng do RoomTypeSeeder đảm nhiệm (chạy sau, có mô tả đầy đủ + ảnh
 * thật) — KHÔNG seed room_types ở đây nữa. Trước đây seeder này còn có thêm
 * seedRoomTypes() tạo 5 room type cùng slug nhưng dữ liệu sơ sài hơn; vì
 * chạy trước RoomTypeSeeder trong DatabaseSeeder, firstOrCreate() theo slug
 * khiến bản ghi "thắng" luôn là bản sơ sài này còn dữ liệu đẹp của
 * RoomTypeSeeder bị lặng lẽ bỏ qua (chỉ ảnh được gắn thêm vào sau).
 */
class HotelInfoSeeder extends Seeder
{
    public function run(ImageService $imageService): void
    {
        $this->seedAmenities();
        $this->seedHotelInfo($imageService);
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

    private function seedHotelInfo(ImageService $imageService): void
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

        if ($hotel->images()->count() === 0) {
            $imageService->syncHotelInfoImages($hotel, [
                'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&h=800&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=1200&h=800&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=1200&h=800&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?w=1200&h=800&fit=crop&auto=format',
            ]);
        }
    }
}
