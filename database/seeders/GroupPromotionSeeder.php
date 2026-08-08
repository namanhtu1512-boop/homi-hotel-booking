<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class GroupPromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promotions = [
            [
                'code'             => 'GROUP5',
                'name'             => 'Ưu đãi đoàn nhỏ',
                'description'      => 'Giảm 5% cho đoàn từ 5-9 phòng',
                'discount_percent' => 5,
                'discount_amount'  => null,
                'status'           => 'active',
                'stackable'        => false,
                'is_group_promo'   => true,
            ],
            [
                'code'             => 'GROUP10',
                'name'             => 'Ưu đãi đoàn vừa',
                'description'      => 'Giảm 10% cho đoàn từ 10-19 phòng',
                'discount_percent' => 10,
                'discount_amount'  => null,
                'status'           => 'active',
                'stackable'        => false,
                'is_group_promo'   => true,
            ],
            [
                'code'             => 'GROUP15',
                'name'             => 'Ưu đãi đoàn lớn',
                'description'      => 'Giảm 15% cho đoàn từ 20-29 phòng',
                'discount_percent' => 15,
                'discount_amount'  => null,
                'status'           => 'active',
                'stackable'        => false,
                'is_group_promo'   => true,
            ],
            [
                'code'             => 'GROUP20',
                'name'             => 'Ưu đãi đoàn siêu lớn',
                'description'      => 'Giảm 20% cho đoàn từ 30 phòng trở lên',
                'discount_percent' => 20,
                'discount_amount'  => null,
                'status'           => 'active',
                'stackable'        => false,
                'is_group_promo'   => true,
            ],
            [
                'code'             => 'CORPGROUP',
                'name'             => 'Ưu đãi khách công ty/tổ chức',
                'description'      => 'Ưu đãi 500.000đ cho khách công ty/tổ chức từ 15 phòng',
                'discount_percent' => null,
                'discount_amount'  => 500000,
                'status'           => 'active',
                'stackable'        => false,
                'is_group_promo'   => true,
            ],
            [
                'code'             => 'EARLYGROUP',
                'name'             => 'Ưu đãi đặt sớm cho đoàn',
                'description'      => 'Giảm thêm 5% nếu đặt trước 30 ngày (có thể cộng dồn với các mã trên)',
                'discount_percent' => 5,
                'discount_amount'  => null,
                'status'           => 'active',
                'stackable'        => true,
                'is_group_promo'   => true,
            ],
        ];

        // updateOrCreate (không phải create) — chạy seeder nhiều lần không lỗi
        // trùng unique code, đồng thời tự cập nhật lại nếu mô tả/mức giảm đổi.
        foreach ($promotions as $promotion) {
            Promotion::updateOrCreate(['code' => $promotion['code']], $promotion);
        }
    }
}
