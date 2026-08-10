<?php

namespace Database\Seeders;

use App\Models\SurchargeItem;
use Illuminate\Database\Seeder;

class SurchargeItemSeeder extends Seeder
{
    /**
     * Dữ liệu chuyển thể từ danh sách hardcode cũ trong
     * resources/views/partials/surcharge-item-select.blade.php.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Khăn mặt', 'price' => 100000],
            ['name' => 'Khăn tắm', 'price' => 250000],
            ['name' => 'Áo choàng tắm', 'price' => 500000],
            ['name' => 'Dép đi trong phòng', 'price' => 50000],
            ['name' => 'Gối', 'price' => 300000],
            ['name' => 'Ruột gối', 'price' => 200000],
            ['name' => 'Chăn', 'price' => 600000],
            ['name' => 'Ga giường', 'price' => 500000],
            ['name' => 'Máy sấy tóc', 'price' => 700000],
            ['name' => 'Ấm đun siêu tốc', 'price' => 500000],
            ['name' => 'Điều khiển TV', 'price' => 300000],
            ['name' => 'Ly thủy tinh', 'price' => 100000],
            ['name' => 'Cốc sứ', 'price' => 120000],
            ['name' => 'Bình nước', 'price' => 200000],
            ['name' => 'Thùng rác', 'price' => 250000],
            ['name' => 'Đèn ngủ', 'price' => 800000],
            ['name' => 'Ghế', 'price' => 1000000],
            ['name' => 'TV', 'price' => null, 'price_note' => '8.000.000–15.000.000đ (tùy mức độ hư hỏng)'],
            ['name' => 'Tủ lạnh mini', 'price' => null, 'price_note' => '4.000.000–7.000.000đ (tùy mức độ hư hỏng)'],
            ['name' => 'Điều hòa', 'price' => null, 'price_note' => '8.000.000–15.000.000đ (tùy mức độ hư hỏng)'],
        ];

        foreach ($items as $item) {
            SurchargeItem::firstOrCreate(['name' => $item['name']], $item + ['status' => 'active']);
        }
    }
}
