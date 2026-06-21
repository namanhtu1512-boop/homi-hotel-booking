<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\HotelInfo;
use App\Services\ImageService;
use Illuminate\Database\Seeder;

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
            ['name' => 'Wifi miễn phí', 'icon' => 'wifi'],
            ['name' => 'Bãi đỗ xe', 'icon' => 'parking'],
            ['name' => 'Hồ bơi', 'icon' => 'pool'],
            ['name' => 'Phòng gym', 'icon' => 'gym'],
            ['name' => 'Nhà hàng', 'icon' => 'restaurant'],
            ['name' => 'Quầy bar', 'icon' => 'bar'],
            ['name' => 'Spa', 'icon' => 'spa'],
            ['name' => 'Dịch vụ phòng 24/7', 'icon' => 'room-service'],
            ['name' => 'Điều hòa', 'icon' => 'ac'],
            ['name' => 'Thang máy', 'icon' => 'elevator'],
        ];

        foreach ($amenities as $amenity) {
            Amenity::firstOrCreate(['name' => $amenity['name']], $amenity);
        }
    }

    private function seedHotelInfo(ImageService $imageService): void
    {
        $hotel = HotelInfo::updateOrCreate(
            ['id' => 1],
            [
                'name'           => 'Homi Sài Gòn Hotel',
                'description'    => 'Khách sạn 5 sao ngay trung tâm quận 1, gần chợ Bến Thành và phố đi bộ Nguyễn Huệ.',
                'address'        => '88 Lê Lợi, Quận 1, TP Hồ Chí Minh',
                'hotline'        => '1900 0000',
                'email'          => 'support@homi.test',
                'check_in_time'  => '14:00',
                'check_out_time' => '12:00',
                'policies'       => 'Hủy miễn phí trước 24 giờ. Không cho phép thú cưng. Trẻ em dưới 6 tuổi miễn phí.',
                'star_rating'    => 5,
                'is_open'        => true,
            ]
        );

        $hotel->amenities()->sync(
            Amenity::whereIn('name', [
                'Wifi miễn phí', 'Bãi đỗ xe', 'Hồ bơi', 'Phòng gym',
                'Nhà hàng', 'Quầy bar', 'Spa', 'Dịch vụ phòng 24/7',
                'Điều hòa', 'Thang máy',
            ])->pluck('id')
        );

        if ($hotel->images()->count() === 0) {
            $imageService->syncHotelImages($hotel, [
                'https://picsum.photos/seed/homi-hotel-lobby/1200/800',
                'https://picsum.photos/seed/homi-hotel-pool/1200/800',
                'https://picsum.photos/seed/homi-hotel-room/1200/800',
                'https://picsum.photos/seed/homi-hotel-exterior/1200/800',
            ]);
        }
    }
}
