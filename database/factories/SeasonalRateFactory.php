<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonalRateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_type_id'     => null,
            'label'            => 'Mùa ' . $this->faker->unique()->word(),
            'start_date'       => now()->addDays(30)->toDateString(),
            'end_date'         => now()->addDays(37)->toDateString(),
            'adjustment_type'  => 'percent',
            'adjustment_value' => 20,
            'status'           => 'active',
        ];
    }
}
