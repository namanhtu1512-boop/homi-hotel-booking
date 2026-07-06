<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => 'Dịch vụ ' . $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'price'       => $this->faker->numberBetween(50000, 500000),
            'status'      => 'active',
        ];
    }

    public function hidden(): static
    {
        return $this->state(['status' => 'hidden']);
    }
}
