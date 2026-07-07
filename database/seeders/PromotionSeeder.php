<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promotions = [
            [
                'name'             => 'Ưu đãi hè rực rỡ',
                'code'             => 'SUMMER10',
                'description'      => 'Giảm 10% tổng hóa đơn cho mọi đơn đặt phòng trong mùa hè.',
                'discount_percent' => 10,
                'starts_at'        => now()->subDays(10),
                'ends_at'          => now()->addMonths(2),
                'status'           => 'active',
            ],
            [
                'name'             => 'Giảm ngay 200K',
                'code'             => 'HOMI200K',
                'description'      => 'Giảm trực tiếp 200.000đ cho đơn đặt phòng từ 2 đêm trở lên.',
                'discount_amount'  => 200000,
                'starts_at'        => now()->subDays(5),
                'ends_at'          => now()->addMonths(1),
                'status'           => 'active',
            ],
            [
                'name'             => 'Ưu đãi đã kết thúc',
                'code'             => 'EXPIRED2025',
                'description'      => 'Mã khuyến mãi minh họa đã hết hạn — không hiển thị ở trang public.',
                'discount_percent' => 15,
                'starts_at'        => now()->subMonths(3),
                'ends_at'          => now()->subMonth(),
                'status'           => 'active',
            ],
        ];

        foreach ($promotions as $promotion) {
            Promotion::firstOrCreate(['code' => $promotion['code']], $promotion);
        }
    }
}
