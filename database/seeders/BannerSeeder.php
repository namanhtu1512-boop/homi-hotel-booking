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
                'image_path' => 'https://images.unsplash.com/photo-1777016844282-46fa8713cdae?w=1600&h=700&fit=crop&auto=format',
                'link_url'   => '/rooms',
                'sort_order' => 1,
                'status'     => 'active',
            ],
            [
                'title'      => 'Phòng Suite sang trọng view sông Hàn',
                'subtitle'   => 'Trải nghiệm đẳng cấp cho dịp đặc biệt của bạn',
                'image_path' => 'https://images.unsplash.com/photo-1776763018972-588e27bf6511?w=1600&h=700&fit=crop&auto=format',
                'link_url'   => '/rooms',
                'sort_order' => 2,
                'status'     => 'active',
            ],
            [
                'title'      => 'Ưu đãi đặt sớm — Giảm đến 20%',
                'subtitle'   => 'Áp dụng mã EARLYBIRD20 khi đặt phòng trước 30 ngày',
                'image_path' => 'https://images.unsplash.com/photo-1771775529138-a7a20ba7e032?w=1600&h=700&fit=crop&auto=format',
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
