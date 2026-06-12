<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HotelFactory extends Factory
{
    public function definition(): array
    {
        $cities = ['Hà Nội', 'TP Hồ Chí Minh', 'Đà Nẵng', 'Hội An', 'Nha Trang', 'Phú Quốc', 'Huế', 'Đà Lạt'];
        $city   = $this->faker->randomElement($cities);
        $name   = 'Homi ' . $city . ' Hotel ' . $this->faker->unique()->numberBetween(1, 999);

        return [
            'name'        => $name,
            'slug'        => Str::slug($name),
            'city'        => $city,
            'district'    => $this->faker->word(),
            'address'     => $this->faker->streetAddress() . ', ' . $city,
            'description' => $this->faker->paragraph(),
            'star_rating' => $this->faker->numberBetween(2, 5),
            'status'      => 'active',
        ];
    }

    public function hidden(): static
    {
        return $this->state(['status' => 'hidden']);
    }
}
