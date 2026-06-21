<?php

namespace Database\Seeders;

use App\Models\RoomType;
use App\Services\ImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoomTypeSeeder extends Seeder
{
    public function run(ImageService $imageService): void
    {
        $rooms = [
            [
                'name'            => 'Phòng Standard',
                'description'     => 'Phòng tiêu chuẩn view thành phố, nội thất hiện đại.',
                'price_per_night' => 900000,
                'capacity'        => 2,
                'bed_type'        => '1 giường đôi',
                'area'            => 28,
                'total_rooms'     => 15,
                'image_seed'      => 'homi-room-standard',
            ],
            [
                'name'            => 'Phòng Deluxe',
                'description'     => 'Phòng deluxe view trung tâm, thiết kế sang trọng.',
                'price_per_night' => 1400000,
                'capacity'        => 2,
                'bed_type'        => '1 giường đôi lớn',
                'area'            => 35,
                'total_rooms'     => 10,
                'image_seed'      => 'homi-room-deluxe',
            ],
            [
                'name'            => 'Phòng Suite',
                'description'     => 'Suite cao cấp với phòng khách riêng và bồn tắm.',
                'price_per_night' => 2800000,
                'capacity'        => 2,
                'bed_type'        => '1 giường đôi lớn',
                'area'            => 60,
                'total_rooms'     => 4,
                'image_seed'      => 'homi-room-suite',
            ],
            [
                'name'            => 'Phòng Family',
                'description'     => 'Phòng gia đình rộng rãi, phù hợp cho nhóm nhỏ.',
                'price_per_night' => 1900000,
                'capacity'        => 4,
                'bed_type'        => '2 giường đôi',
                'area'            => 45,
                'total_rooms'     => 6,
                'image_seed'      => 'homi-room-family',
            ],
        ];

        foreach ($rooms as $room) {
            $imageSeed = $room['image_seed'];
            unset($room['image_seed']);

            $roomType = RoomType::firstOrCreate(
                ['slug' => Str::slug($room['name'])],
                [...$room, 'slug' => Str::slug($room['name']), 'status' => 'active'],
            );

            if ($roomType->images()->count() === 0) {
                $imageService->syncRoomTypeImages($roomType, [
                    "https://picsum.photos/seed/{$imageSeed}-1/900/600",
                    "https://picsum.photos/seed/{$imageSeed}-2/900/600",
                ]);
            }
        }
    }
}
