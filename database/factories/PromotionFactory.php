<?php

namespace Database\Factories;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        return [
            'name'             => 'Khuyến mãi ' . fake()->unique()->word(),
            'code'             => strtoupper(fake()->unique()->bothify('PROMO###??')),
            'description'      => fake()->sentence(),
            'discount_percent' => 10,
            'discount_amount'  => null,
            'starts_at'        => null,
            'ends_at'          => null,
            'status'           => 'active',
            'stackable'        => false,
            'is_group_promo'   => false,
        ];
    }

    public function groupPromo(): static
    {
        return $this->state(fn () => ['is_group_promo' => true]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subMonths(3),
            'ends_at'   => now()->subDay(),
        ]);
    }
}
