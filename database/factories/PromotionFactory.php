<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'             => 'Ưu đãi ' . $this->faker->unique()->word(),
            'code'             => $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'description'      => $this->faker->sentence(),
            'discount_percent' => 10,
            'discount_amount'  => null,
            'starts_at'        => now()->subDays(1),
            'ends_at'          => now()->addMonths(1),
            'status'           => 'active',
            'stackable'        => false,
        ];
    }

    public function stackable(): static
    {
        return $this->state(['stackable' => true]);
    }
}
