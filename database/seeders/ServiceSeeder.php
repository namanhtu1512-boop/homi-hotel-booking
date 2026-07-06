<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name'        => 'Ăn sáng buffet',
                'description' => 'Buffet sáng tại nhà hàng khách sạn, phục vụ 6:00 - 10:00.',
                'price'       => 150000,
                'status'      => 'active',
            ],
            [
                'name'        => 'Đưa đón sân bay',
                'description' => 'Xe đưa đón 1 chiều giữa sân bay và khách sạn.',
                'price'       => 300000,
                'status'      => 'active',
            ],
            [
                'name'        => 'Trả phòng muộn (đến 18:00)',
                'description' => 'Giữ phòng đến 18:00 thay vì giờ trả phòng tiêu chuẩn.',
                'price'       => 200000,
                'status'      => 'active',
            ],
            [
                'name'        => 'Giường phụ',
                'description' => 'Thêm 1 giường phụ trong phòng.',
                'price'       => 250000,
                'status'      => 'active',
            ],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(['name' => $service['name']], $service);
        }
    }
}
