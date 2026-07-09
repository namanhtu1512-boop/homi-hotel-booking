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
                    'https://images.unsplash.com/photo-1746549855427-57e6da7040db?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1731336478850-6bce7235e320?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1578898886225-c7c894047899?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1605346434674-a440ca4dc4c0?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1647792855184-af42f1720b91?w=900&h=600&fit=crop&auto=format',
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
                    'https://images.unsplash.com/photo-1737517302831-e7b8a8eaa97c?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1741506131058-533fcf894483?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1662990782404-a5d704ea323a?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1645131506334-bb66f3f02bcc?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=900&h=600&fit=crop&auto=format',
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
                    'https://images.unsplash.com/photo-1777016844282-46fa8713cdae?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1667125095636-dce94dcbdd96?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1662841540530-2f04bb3291e8?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1552858725-693709cc17c7?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1639678349557-ffe5bed73ce7?w=900&h=600&fit=crop&auto=format',
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
                    'https://images.unsplash.com/photo-1771775529138-a7a20ba7e032?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1629140727571-9b5c6f6267b4?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1631049552057-403cdb8f0658?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1703783010857-9bd7a7b97c50?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1631049035257-02039c597992?w=900&h=600&fit=crop&auto=format',
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
                    'https://images.unsplash.com/photo-1776763018972-588e27bf6511?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1631049307379-e96858bda538?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1631049422186-4b0569fed517?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1631049035634-c04c637651b1?w=900&h=600&fit=crop&auto=format',
                    'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=900&h=600&fit=crop&auto=format',
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
