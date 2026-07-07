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
                'title'      => 'Chào mừng đến với Homi Hotel',
                'subtitle'   => 'Nghỉ dưỡng giữa lòng thành phố, tận hưởng dịch vụ tận tâm.',
                'image_path' => 'https://picsum.photos/seed/homi-banner-1/1600/600',
                'link_url'   => '/rooms',
                'sort_order' => 0,
                'status'     => 'active',
            ],
            [
                'title'      => 'Ưu đãi hè rực rỡ — Giảm đến 10%',
                'subtitle'   => 'Áp dụng cho mọi loại phòng, đặt ngay hôm nay.',
                'image_path' => 'https://picsum.photos/seed/homi-banner-2/1600/600',
                'link_url'   => '/promotions',
                'sort_order' => 1,
                'status'     => 'active',
            ],
        ];

        foreach ($banners as $banner) {
            Banner::firstOrCreate(['title' => $banner['title']], $banner);
        }
    }
}
