<?php

namespace Database\Factories;

use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_type_id'        => RoomType::factory(),
            'room_number'         => (string) $this->faker->unique()->numberBetween(100, 9999),
            'housekeeping_status' => 'clean',
        ];
    }

    public function dirty(): static
    {
        return $this->state(['housekeeping_status' => 'dirty']);
    }
}
