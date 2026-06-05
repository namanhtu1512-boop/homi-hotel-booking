<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Customer Demo',
                'email' => 'customer@homi.test',
                'phone' => '0900000001',
                'address' => 'Hà Nội',
                'role' => 'customer',
            ],
            [
                'name' => 'Staff Demo',
                'email' => 'staff@homi.test',
                'phone' => '0900000002',
                'address' => 'Đà Nẵng',
                'role' => 'staff',
            ],
            [
                'name' => 'Admin Demo',
                'email' => 'admin@homi.test',
                'phone' => '0900000003',
                'address' => 'TP Hồ Chí Minh',
                'role' => 'admin',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'address' => $user['address'],
                    'role' => $user['role'],
                    'status' => 'active',
                    'password' => Hash::make('123456'),
                ]
            );
        }
    }
}
