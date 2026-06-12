<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Hotel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAmenities();
        $this->seedHotels();
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

    private function seedHotels(): void
    {
        $hotels = [
            [
                'name'        => 'Homi Đà Nẵng Hotel',
                'city'        => 'Đà Nẵng',
                'district'    => 'Hải Châu',
                'address'     => '123 Bạch Đằng, Hải Châu, Đà Nẵng',
                'description' => 'Khách sạn trung tâm thành phố, gần sông Hàn, phù hợp cho du lịch và công tác.',
                'star_rating' => 4,
                'amenities'   => ['Wifi miễn phí', 'Bãi đỗ xe', 'Hồ bơi', 'Nhà hàng', 'Điều hòa', 'Thang máy'],
                'rooms'       => [
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
                        'name'            => 'Phòng Deluxe',
                        'description'     => 'Phòng rộng, view thành phố, phù hợp cho cặp đôi hoặc khách công tác.',
                        'price_per_night' => 950000,
                        'capacity'        => 2,
                        'bed_type'        => '1 giường đôi lớn',
                        'area'            => 32,
                        'total_rooms'     => 8,
                    ],
                ],
            ],
            [
                'name'        => 'Homi Hà Nội Hotel',
                'city'        => 'Hà Nội',
                'district'    => 'Hoàn Kiếm',
                'address'     => '45 Tràng Tiền, Hoàn Kiếm, Hà Nội',
                'description' => 'Khách sạn gần phố cổ, thuận tiện tham quan và di chuyển.',
                'star_rating' => 4,
                'amenities'   => ['Wifi miễn phí', 'Nhà hàng', 'Quầy bar', 'Phòng gym', 'Điều hòa', 'Thang máy'],
                'rooms'       => [
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
                        'name'            => 'Phòng Family',
                        'description'     => 'Phòng gia đình rộng rãi, phù hợp cho nhóm nhỏ.',
                        'price_per_night' => 1400000,
                        'capacity'        => 4,
                        'bed_type'        => '2 giường đôi',
                        'area'            => 45,
                        'total_rooms'     => 6,
                    ],
                ],
            ],
        ];

        foreach ($hotels as $hotelData) {
            $amenityNames = $hotelData['amenities'];
            $rooms        = $hotelData['rooms'];
            unset($hotelData['amenities'], $hotelData['rooms']);

            $hotel = Hotel::create([
                ...$hotelData,
                'slug'   => Str::slug($hotelData['name']),
                'status' => 'active',
            ]);

            $hotel->amenities()->sync(
                Amenity::whereIn('name', $amenityNames)->pluck('id')
            );

            $hotel->roomTypes()->createMany(
                array_map(fn($room) => [
                    ...$room,
                    'slug'   => Str::slug($room['name']),
                    'status' => 'active',
                ], $rooms)
            );
        }
    }
}
