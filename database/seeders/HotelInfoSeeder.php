<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\HotelInfo;
use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed dữ liệu cho khách sạn singleton Homi (1 bản ghi hotel_info duy
 * nhất) cùng danh sách loại phòng. Vì hệ thống chỉ vận hành 1 khách sạn,
 * không seed nhiều bản ghi hotel — chỉ seed đúng 1 hotel_info + tiện ích
 * + loại phòng.
 */
class HotelInfoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAmenities();
        $this->seedHotelInfo();
        $this->seedRoomTypes();
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

    private function seedRoomTypes(): void
    {
        $rooms = [
            [
                'name'            => 'Phòng Standard',
                'description'     => 'Phòng tiêu chuẩn, đầy đủ tiện nghi cơ bản.',
                'price_per_night' => 650000,
                'capacity'        => 2,
                'bed_type'        => '1 giường đôi',
                'area'            => 25,
                'total_rooms'     => 10,
            ],
            [
                'name'            => 'Phòng Superior',
                'description'     => 'Phòng tiện nghi, phù hợp cho khách lưu trú ngắn ngày.',
                'price_per_night' => 800000,
                'capacity'        => 2,
                'bed_type'        => '2 giường đơn',
                'area'            => 28,
                'total_rooms'     => 12,
            ],
            [
                'name'            => 'Phòng Deluxe',
                'description'     => 'Phòng rộng, view thành phố, phù hợp cho cặp đôi hoặc khách công tác.',
                'price_per_night' => 950000,
                'capacity'        => 2,
                'bed_type'        => '1 giường đôi lớn',
                'area'            => 32,
                'total_rooms'     => 8,
            ],
            [
                'name'            => 'Phòng Family',
                'description'     => 'Phòng gia đình rộng rãi, phù hợp cho nhóm nhỏ.',
                'price_per_night' => 1400000,
                'capacity'        => 4,
                'bed_type'        => '2 giường đôi',
                'area'            => 45,
                'total_rooms'     => 6,
            ],
            [
                'name'            => 'Phòng Suite',
                'description'     => 'Suite cao cấp với phòng khách riêng và bồn tắm.',
                'price_per_night' => 2800000,
                'capacity'        => 2,
                'bed_type'        => '1 giường đôi lớn',
                'area'            => 60,
                'total_rooms'     => 4,
            ],
        ];

        foreach ($rooms as $room) {
            RoomType::firstOrCreate(
                ['slug' => Str::slug($room['name'])],
                [...$room, 'slug' => Str::slug($room['name']), 'status' => 'active'],
            );
        }
    }
}
