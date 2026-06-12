<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'     => fake('vi_VN')->name(),
            'email'    => fake()->unique()->safeEmail(),
            'phone'    => '09' . fake()->numerify('########'),
            'address'  => fake('vi_VN')->city(),
            'role'     => 'customer',
            'status'   => 'active',
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    public function staff(): static
    {
        return $this->state(['role' => 'staff']);
    }

    public function customer(): static
    {
        return $this->state(['role' => 'customer']);
    }

    public function locked(): static
    {
        return $this->state(['status' => 'locked']);
    }
}
