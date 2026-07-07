<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            HotelInfoSeeder::class,
            RoomTypeSeeder::class,
            BookingSeeder::class,
            PromotionSeeder::class,
            BannerSeeder::class,
        ]);
    }
}
