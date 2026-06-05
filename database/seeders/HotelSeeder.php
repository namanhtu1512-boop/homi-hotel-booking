<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $hotels = [
            [
                'name' => 'Homi Đà Nẵng Hotel',
                'city' => 'Đà Nẵng',
                'district' => 'Hải Châu',
                'address' => '123 Bạch Đằng, Hải Châu, Đà Nẵng',
                'description' => 'Khách sạn trung tâm thành phố, gần sông Hàn, phù hợp cho du lịch và công tác.',
                'star_rating' => 4,
                'rooms' => [
                    [
                        'name' => 'Phòng Standard',
                        'description' => 'Phòng tiêu chuẩn, đầy đủ tiện nghi cơ bản.',
                        'price_per_night' => 650000,
                        'capacity' => 2,
                        'bed_type' => '1 giường đôi',
                        'area' => 25,
                        'total_rooms' => 10,
                    ],
                    [
                        'name' => 'Phòng Deluxe',
                        'description' => 'Phòng rộng, view thành phố, phù hợp cho cặp đôi hoặc khách công tác.',
                        'price_per_night' => 950000,
                        'capacity' => 2,
                        'bed_type' => '1 giường đôi lớn',
                        'area' => 32,
                        'total_rooms' => 8,
                    ],
                ],
            ],
            [
                'name' => 'Homi Hà Nội Hotel',
                'city' => 'Hà Nội',
                'district' => 'Hoàn Kiếm',
                'address' => '45 Tràng Tiền, Hoàn Kiếm, Hà Nội',
                'description' => 'Khách sạn gần phố cổ, thuận tiện tham quan và di chuyển.',
                'star_rating' => 4,
                'rooms' => [
                    [
                        'name' => 'Phòng Superior',
                        'description' => 'Phòng tiện nghi, phù hợp cho khách lưu trú ngắn ngày.',
                        'price_per_night' => 800000,
                        'capacity' => 2,
                        'bed_type' => '2 giường đơn',
                        'area' => 28,
                        'total_rooms' => 12,
                    ],
                    [
                        'name' => 'Phòng Family',
                        'description' => 'Phòng gia đình rộng rãi, phù hợp cho nhóm nhỏ.',
                        'price_per_night' => 1400000,
                        'capacity' => 4,
                        'bed_type' => '2 giường đôi',
                        'area' => 45,
                        'total_rooms' => 6,
                    ],
                ],
            ],
        ];

        foreach ($hotels as $hotelData) {
            $rooms = $hotelData['rooms'];
            unset($hotelData['rooms']);

            $hotel = Hotel::create([
                'name' => $hotelData['name'],
                'slug' => Str::slug($hotelData['name']),
                'city' => $hotelData['city'],
                'district' => $hotelData['district'],
                'address' => $hotelData['address'],
                'description' => $hotelData['description'],
                'star_rating' => $hotelData['star_rating'],
                'status' => 'active',
            ]);

            foreach ($rooms as $room) {
                RoomType::create([
                    'hotel_id' => $hotel->id,
                    'name' => $room['name'],
                    'slug' => Str::slug($room['name']),
                    'description' => $room['description'],
                    'price_per_night' => $room['price_per_night'],
                    'capacity' => $room['capacity'],
                    'bed_type' => $room['bed_type'],
                    'area' => $room['area'],
                    'total_rooms' => $room['total_rooms'],
                    'status' => 'active',
                ]);
            }
        }
    }
}
