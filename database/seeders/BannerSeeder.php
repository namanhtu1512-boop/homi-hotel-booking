<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'title'      => 'Kỳ nghỉ đáng nhớ tại Homi Hotel',
                'subtitle'   => 'Ưu đãi lên đến 20% cho đặt phòng mùa hè này',
                'image_path' => 'https://picsum.photos/seed/homi-banner-1/1600/700',
                'link_url'   => '/rooms',
                'sort_order' => 1,
                'status'     => 'active',
            ],
            [
                'title'      => 'Phòng Suite sang trọng view sông Hàn',
                'subtitle'   => 'Trải nghiệm đẳng cấp cho dịp đặc biệt của bạn',
                'image_path' => 'https://picsum.photos/seed/homi-banner-2/1600/700',
                'link_url'   => '/rooms',
                'sort_order' => 2,
                'status'     => 'active',
            ],
            [
                'title'      => 'Ưu đãi đặt sớm — Giảm đến 20%',
                'subtitle'   => 'Áp dụng mã EARLYBIRD20 khi đặt phòng trước 30 ngày',
                'image_path' => 'https://picsum.photos/seed/homi-banner-3/1600/700',
                'link_url'   => '/promotions',
                'sort_order' => 3,
                'status'     => 'active',
            ],
        ];

        foreach ($banners as $banner) {
            Banner::firstOrCreate(['title' => $banner['title']], $banner);
        }
    }
}
