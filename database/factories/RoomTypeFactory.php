<?php

namespace Database\Factories;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoomTypeFactory extends Factory
{
    public function definition(): array
    {
        $names    = ['Standard', 'Superior', 'Deluxe', 'Junior Suite', 'Suite', 'Family', 'Executive'];
        $bedTypes = ['1 giường đôi', '2 giường đơn', '1 giường đôi lớn', '2 giường đôi'];
        $name     = $this->faker->randomElement($names) . ' ' . $this->faker->unique()->numberBetween(1, 9999);

        return [
            'hotel_id'       => Hotel::factory(),
            'name'           => $name,
            'slug'           => Str::slug($name),
            'description'    => $this->faker->paragraph(),
            'price_per_night'=> $this->faker->numberBetween(400, 5000) * 1000,
            'capacity'       => $this->faker->numberBetween(1, 4),
            'bed_type'       => $this->faker->randomElement($bedTypes),
            'area'           => $this->faker->numberBetween(18, 80),
            'total_rooms'    => $this->faker->numberBetween(5, 20),
            'status'         => 'active',
        ];
    }

    public function hidden(): static
    {
        return $this->state(['status' => 'hidden']);
    }

    public function maintenance(): static
    {
        return $this->state(['status' => 'maintenance']);
    }
}
