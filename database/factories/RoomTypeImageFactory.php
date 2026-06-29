<?php

namespace Database\Factories;

use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomTypeImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'path'         => 'https://picsum.photos/seed/' . $this->faker->unique()->word() . '/900/600',
            'sort_order'   => $this->faker->numberBetween(0, 10),
        ];
    }
}
