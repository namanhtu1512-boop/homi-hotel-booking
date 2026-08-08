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
            RoomAmenitySeeder::class,
            RoomSeeder::class,
            BookingSeeder::class,
            PromotionSeeder::class,
            GroupPromotionSeeder::class,
            BannerSeeder::class,
            NewsSeeder::class,
            ContactMessageSeeder::class,
            ServiceSeeder::class,
        ]);
    }
}
