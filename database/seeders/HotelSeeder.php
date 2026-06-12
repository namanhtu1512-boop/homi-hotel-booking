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
            // ---- Đà Nẵng ----
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
            // ---- Hà Nội ----
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
            // ---- TP. Hồ Chí Minh ----
            [
                'name'        => 'Homi Sài Gòn Hotel',
                'city'        => 'TP Hồ Chí Minh',
                'district'    => 'Quận 1',
                'address'     => '88 Lê Lợi, Quận 1, TP Hồ Chí Minh',
                'description' => 'Khách sạn 5 sao ngay trung tâm quận 1, gần chợ Bến Thành và phố đi bộ Nguyễn Huệ.',
                'star_rating' => 5,
                'amenities'   => ['Wifi miễn phí', 'Bãi đỗ xe', 'Hồ bơi', 'Phòng gym', 'Nhà hàng', 'Quầy bar', 'Spa', 'Dịch vụ phòng 24/7', 'Điều hòa', 'Thang máy'],
                'rooms'       => [
                    [
                        'name'            => 'Phòng Standard',
                        'description'     => 'Phòng tiêu chuẩn view thành phố, nội thất hiện đại.',
                        'price_per_night' => 900000,
                        'capacity'        => 2,
                        'bed_type'        => '1 giường đôi',
                        'area'            => 28,
                        'total_rooms'     => 15,
                    ],
                    [
                        'name'            => 'Phòng Deluxe',
                        'description'     => 'Phòng deluxe view trung tâm, thiết kế sang trọng.',
                        'price_per_night' => 1400000,
                        'capacity'        => 2,
                        'bed_type'        => '1 giường đôi lớn',
                        'area'            => 35,
                        'total_rooms'     => 10,
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
                ],
            ],
            // ---- Đà Lạt ----
            [
                'name'        => 'Homi Đà Lạt Resort',
                'city'        => 'Đà Lạt',
                'district'    => 'Phường 1',
                'address'     => '12 Trần Phú, Phường 1, Đà Lạt',
                'description' => 'Resort view đồi thông yên tĩnh, không khí mát mẻ quanh năm, phù hợp nghỉ dưỡng cặp đôi và gia đình.',
                'star_rating' => 3,
                'amenities'   => ['Wifi miễn phí', 'Nhà hàng', 'Bãi đỗ xe', 'Điều hòa'],
                'rooms'       => [
                    [
                        'name'            => 'Phòng Standard',
                        'description'     => 'Phòng ấm cúng, view vườn thông.',
                        'price_per_night' => 500000,
                        'capacity'        => 2,
                        'bed_type'        => '2 giường đơn',
                        'area'            => 22,
                        'total_rooms'     => 14,
                    ],
                    [
                        'name'            => 'Phòng Deluxe',
                        'description'     => 'Phòng rộng có ban công view đồi, lò sưởi.',
                        'price_per_night' => 750000,
                        'capacity'        => 2,
                        'bed_type'        => '1 giường đôi lớn',
                        'area'            => 30,
                        'total_rooms'     => 8,
                    ],
                ],
            ],
            // ---- Nha Trang ----
            [
                'name'        => 'Homi Nha Trang Beach Hotel',
                'city'        => 'Nha Trang',
                'district'    => 'Lộc Thọ',
                'address'     => '6 Trần Phú, Lộc Thọ, Nha Trang',
                'description' => 'Khách sạn mặt biển Trần Phú, cách bãi biển Nha Trang 50m, view biển toàn panorama.',
                'star_rating' => 4,
                'amenities'   => ['Wifi miễn phí', 'Hồ bơi', 'Nhà hàng', 'Spa', 'Bãi đỗ xe', 'Điều hòa', 'Thang máy'],
                'rooms'       => [
                    [
                        'name'            => 'Phòng Standard',
                        'description'     => 'Phòng tiêu chuẩn, view thành phố.',
                        'price_per_night' => 700000,
                        'capacity'        => 2,
                        'bed_type'        => '1 giường đôi',
                        'area'            => 26,
                        'total_rooms'     => 12,
                    ],
                    [
                        'name'            => 'Phòng Deluxe Biển',
                        'description'     => 'Phòng view biển trực tiếp, ban công riêng.',
                        'price_per_night' => 1100000,
                        'capacity'        => 2,
                        'bed_type'        => '1 giường đôi lớn',
                        'area'            => 33,
                        'total_rooms'     => 10,
                    ],
                ],
            ],
            // ---- Phú Quốc ----
            [
                'name'        => 'Homi Phú Quốc Resort',
                'city'        => 'Phú Quốc',
                'district'    => 'Dương Đông',
                'address'     => '118 Trần Hưng Đạo, Dương Đông, Phú Quốc',
                'description' => 'Resort 5 sao bên bờ biển Phú Quốc, bãi biển riêng, hồ bơi vô cực, dịch vụ đẳng cấp.',
                'star_rating' => 5,
                'amenities'   => ['Wifi miễn phí', 'Hồ bơi', 'Nhà hàng', 'Quầy bar', 'Spa', 'Bãi đỗ xe', 'Dịch vụ phòng 24/7', 'Điều hòa'],
                'rooms'       => [
                    [
                        'name'            => 'Phòng Garden View',
                        'description'     => 'Phòng view vườn nhiệt đới, không gian yên tĩnh.',
                        'price_per_night' => 1200000,
                        'capacity'        => 2,
                        'bed_type'        => '1 giường đôi lớn',
                        'area'            => 38,
                        'total_rooms'     => 12,
                    ],
                    [
                        'name'            => 'Phòng Ocean View',
                        'description'     => 'Phòng view biển trực diện, ban công lớn.',
                        'price_per_night' => 2000000,
                        'capacity'        => 2,
                        'bed_type'        => '1 giường đôi lớn',
                        'area'            => 45,
                        'total_rooms'     => 8,
                    ],
                    [
                        'name'            => 'Villa Biển',
                        'description'     => 'Villa riêng, hồ bơi riêng, lối ra biển trực tiếp.',
                        'price_per_night' => 4500000,
                        'capacity'        => 4,
                        'bed_type'        => '2 giường đôi lớn',
                        'area'            => 90,
                        'total_rooms'     => 3,
                    ],
                ],
            ],
            // ---- Hội An ----
            [
                'name'        => 'Homi Hội An Boutique',
                'city'        => 'Hội An',
                'district'    => 'Minh An',
                'address'     => '24 Trần Phú, Minh An, Hội An',
                'description' => 'Boutique hotel kiến trúc phố cổ Hội An, nằm trong khu phố cổ di sản UNESCO.',
                'star_rating' => 4,
                'amenities'   => ['Wifi miễn phí', 'Nhà hàng', 'Hồ bơi', 'Bãi đỗ xe', 'Điều hòa', 'Thang máy'],
                'rooms'       => [
                    [
                        'name'            => 'Phòng Classic',
                        'description'     => 'Phòng phong cách cổ điển Hội An, nội thất gỗ mộc.',
                        'price_per_night' => 650000,
                        'capacity'        => 2,
                        'bed_type'        => '1 giường đôi',
                        'area'            => 24,
                        'total_rooms'     => 10,
                    ],
                    [
                        'name'            => 'Phòng Deluxe',
                        'description'     => 'Phòng rộng view phố cổ, ban công riêng.',
                        'price_per_night' => 950000,
                        'capacity'        => 2,
                        'bed_type'        => '1 giường đôi lớn',
                        'area'            => 32,
                        'total_rooms'     => 7,
                    ],
                ],
            ],
        ];

        foreach ($hotels as $hotelData) {
            $amenityNames = $hotelData['amenities'];
            $rooms        = $hotelData['rooms'];
            unset($hotelData['amenities'], $hotelData['rooms']);

            $slug  = Str::slug($hotelData['name']);
            $hotel = Hotel::firstOrCreate(
                ['slug' => $slug],
                [...$hotelData, 'slug' => $slug, 'status' => 'active'],
            );

            $hotel->amenities()->sync(
                Amenity::whereIn('name', $amenityNames)->pluck('id')
            );

            foreach ($rooms as $room) {
                $hotel->roomTypes()->firstOrCreate(
                    ['slug' => Str::slug($room['name']), 'hotel_id' => $hotel->id],
                    [...$room, 'slug' => Str::slug($room['name']), 'status' => 'active'],
                );
            }
        }
    }
}
