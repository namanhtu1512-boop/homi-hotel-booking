<?php

namespace Database\Seeders;

use App\Models\Amenity;
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
                'description'     => 'Phòng Standard tại Homi mang đến không gian lưu trú tiêu chuẩn với đầy đủ tiện nghi cần thiết. Nội thất hiện đại, giường êm ái và tầm nhìn hướng thành phố giúp bạn có những đêm ngủ ngon. Phù hợp cho cặp đôi hoặc khách công tác ngắn ngày.',
                'price_per_night' => 900000,
                'capacity'        => 2,
                'bed_type'        => '1 giường đôi',
                'area'            => 28,
                'total_rooms'     => 15,
                'amenities'       => ['Wifi miễn phí', 'Điều hòa'],
                'images'          => [
                    'https://picsum.photos/seed/homi-std-1/900/600',
                    'https://picsum.photos/seed/homi-std-2/900/600',
                    'https://picsum.photos/seed/homi-std-3/900/600',
                ],
            ],
            [
                'name'            => 'Phòng Superior',
                'description'     => 'Phòng Superior rộng hơn phòng tiêu chuẩn, với 2 giường đơn tiện lợi cho bạn bè hoặc đồng nghiệp đi cùng. Không gian thoáng đãng, ánh sáng tự nhiên tốt và trang thiết bị đầy đủ để đáp ứng mọi nhu cầu lưu trú.',
                'price_per_night' => 1100000,
                'capacity'        => 2,
                'bed_type'        => '2 giường đơn',
                'area'            => 30,
                'total_rooms'     => 12,
                'amenities'       => ['Wifi miễn phí', 'Điều hòa', 'Bãi đỗ xe'],
                'images'          => [
                    'https://picsum.photos/seed/homi-sup-1/900/600',
                    'https://picsum.photos/seed/homi-sup-2/900/600',
                    'https://picsum.photos/seed/homi-sup-3/900/600',
                ],
            ],
            [
                'name'            => 'Phòng Deluxe',
                'description'     => 'Phòng Deluxe sang trọng với view trung tâm thành phố tuyệt đẹp. Thiết kế nội thất cao cấp, giường đôi lớn êm ái, phòng tắm rộng rãi với bồn tắm đứng và tiện nghi đầy đủ. Lựa chọn hoàn hảo cho cặp đôi muốn trải nghiệm lưu trú thoải mái và đẳng cấp.',
                'price_per_night' => 1500000,
                'capacity'        => 2,
                'bed_type'        => '1 giường đôi lớn',
                'area'            => 35,
                'total_rooms'     => 10,
                'amenities'       => ['Wifi miễn phí', 'Điều hòa', 'Hồ bơi', 'Dịch vụ phòng 24/7'],
                'images'          => [
                    'https://picsum.photos/seed/homi-dlx-1/900/600',
                    'https://picsum.photos/seed/homi-dlx-2/900/600',
                    'https://picsum.photos/seed/homi-dlx-3/900/600',
                ],
            ],
            [
                'name'            => 'Phòng Family',
                'description'     => 'Phòng Family rộng rãi với 2 giường đôi, phù hợp cho gia đình có con nhỏ hoặc nhóm bạn đến 4 người. Không gian sinh hoạt chung thoải mái, đầy đủ tiện nghi và trang thiết bị an toàn cho trẻ em. Diện tích 45 m² cho phép cả gia đình di chuyển thoải mái.',
                'price_per_night' => 1900000,
                'capacity'        => 4,
                'bed_type'        => '2 giường đôi',
                'area'            => 45,
                'total_rooms'     => 6,
                'amenities'       => ['Wifi miễn phí', 'Điều hòa', 'Bãi đỗ xe'],
                'images'          => [
                    'https://picsum.photos/seed/homi-fam-1/900/600',
                    'https://picsum.photos/seed/homi-fam-2/900/600',
                    'https://picsum.photos/seed/homi-fam-3/900/600',
                ],
            ],
            [
                'name'            => 'Phòng Suite',
                'description'     => 'Phòng Suite cao cấp nhất tại Homi — không gian riêng biệt với phòng khách sang trọng, phòng ngủ thư giãn và phòng tắm rộng rãi có bồn tắm nằm. Tầm nhìn toàn cảnh sông Hàn, nội thất nhập khẩu cao cấp và dịch vụ butler riêng. Trải nghiệm đỉnh cao cho những dịp đặc biệt.',
                'price_per_night' => 3200000,
                'capacity'        => 2,
                'bed_type'        => '1 giường đôi king',
                'area'            => 65,
                'total_rooms'     => 3,
                'amenities'       => ['Wifi miễn phí', 'Điều hòa', 'Hồ bơi', 'Spa', 'Dịch vụ phòng 24/7'],
                'images'          => [
                    'https://picsum.photos/seed/homi-suite-1/900/600',
                    'https://picsum.photos/seed/homi-suite-2/900/600',
                    'https://picsum.photos/seed/homi-suite-3/900/600',
                ],
            ],
        ];

        foreach ($rooms as $room) {
            $imagePaths     = $room['images'];
            $amenityNames   = $room['amenities'];
            unset($room['images'], $room['amenities']);

            $roomType = RoomType::firstOrCreate(
                ['slug' => Str::slug($room['name'])],
                [...$room, 'slug' => Str::slug($room['name']), 'status' => 'active'],
            );

            if ($roomType->images()->count() === 0) {
                $imageService->syncRoomTypeImages($roomType, $imagePaths);
            }

            if ($roomType->amenities()->count() === 0) {
                $roomType->amenities()->sync(
                    Amenity::whereIn('name', $amenityNames)->pluck('id')
                );
            }
        }
    }
}
