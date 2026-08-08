<?php

namespace Database\Factories;

use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoomTypeFactory extends Factory
{
    protected $model = RoomType::class;

    public function definition(): array
    {
        $name = 'Phòng ' . fake()->unique()->word() . ' ' . fake()->unique()->numberBetween(1, 100000);

        return [
            'name'            => $name,
            'slug'            => Str::slug($name),
            'category'        => 'standard',
            'description'     => fake()->sentence(),
            'price_per_night' => fake()->numberBetween(500, 3000) * 1000,
            'capacity'        => 2,
            'bed_type'        => 'double',
            'area'            => 25,
            'total_rooms'     => 10,
            'status'          => 'active',
            'is_featured'     => false,
        ];
    }
}
