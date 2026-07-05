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
                'code'             => 'HOMISUMMER',
                'description'      => 'Giảm 15% cho mọi đơn đặt phòng trong mùa hè, áp dụng cho tất cả loại phòng.',
                'discount_percent' => 15,
                'discount_amount'  => null,
                'starts_at'        => now()->subDays(10),
                'ends_at'          => now()->addMonths(2),
                'status'           => 'active',
            ],
            [
                'name'             => 'Chào tân khách hàng',
                'code'             => 'WELCOME100K',
                'description'      => 'Giảm ngay 100.000đ cho khách đặt phòng lần đầu tại Homi.',
                'discount_percent' => null,
                'discount_amount'  => 100000,
                'starts_at'        => now()->subMonth(),
                'ends_at'          => now()->addMonths(6),
                'status'           => 'active',
            ],
            [
                'name'             => 'Đặt sớm ưu đãi lớn',
                'code'             => 'EARLYBIRD20',
                'description'      => 'Giảm 20% khi đặt phòng trước ít nhất 30 ngày.',
                'discount_percent' => 20,
                'discount_amount'  => null,
                'starts_at'        => now()->subDays(5),
                'ends_at'          => now()->addMonths(3),
                'status'           => 'active',
            ],
            [
                'name'             => 'Khuyến mãi Tết Nguyên Đán',
                'code'             => 'TETHOMI2026',
                'description'      => 'Chương trình khuyến mãi Tết đã kết thúc, giữ lại để tham khảo lịch sử.',
                'discount_percent' => 10,
                'discount_amount'  => null,
                'starts_at'        => now()->subMonths(6),
                'ends_at'          => now()->subMonths(5),
                'status'           => 'inactive',
            ],
        ];

        foreach ($promotions as $promotion) {
            Promotion::firstOrCreate(['code' => $promotion['code']], $promotion);
        }
    }
}
