<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use Illuminate\Database\Seeder;

class ContactMessageSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            [
                'name'    => 'Trần Thị Mai',
                'email'   => 'mai.tran@example.com',
                'phone'   => '0912345678',
                'message' => 'Cho mình hỏi khách sạn có hỗ trợ đưa đón sân bay không? Mình dự định đặt phòng Suite vào cuối tháng.',
                'status'  => 'unread',
            ],
            [
                'name'    => 'Lê Văn Hùng',
                'email'   => 'hung.le@example.com',
                'phone'   => '0987654321',
                'message' => 'Khách sạn có chỗ đậu ô tô qua đêm không? Đoàn mình đi 2 xe 7 chỗ.',
                'status'  => 'read',
            ],
        ];

        foreach ($messages as $message) {
            ContactMessage::firstOrCreate(['email' => $message['email']], $message);
        }
    }
}
