<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => 'password',
            'phone'             => fake()->numerify('09########'),
            'role'              => 'customer',
            'status'            => 'active',
        ];
    }

    public function staff(): static
    {
        return $this->state(fn () => ['role' => 'staff']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }
}
