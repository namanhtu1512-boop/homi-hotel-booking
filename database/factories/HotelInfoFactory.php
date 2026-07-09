<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HotelInfoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'           => 'Homi Hotel',
            'address'        => $this->faker->streetAddress(),
            'description'    => $this->faker->paragraph(),
            'check_in_time'  => '14:00',
            'check_out_time' => '12:00',
            'policies'       => $this->faker->sentence(),
            'star_rating'    => $this->faker->numberBetween(2, 5),
            'status'         => 'active',
        ];
    }

    public function maintenance(): static
    {
        return $this->state(['status' => 'maintenance']);
    }
}
