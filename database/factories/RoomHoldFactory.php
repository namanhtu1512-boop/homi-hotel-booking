<?php

namespace Database\Factories;

use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoomHoldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'session_id'   => Str::random(40),
            'check_in'     => now()->addDays(30)->toDateString(),
            'check_out'    => now()->addDays(32)->toDateString(),
            'quantity'     => 1,
            'expires_at'   => now()->addMinutes(15),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subMinutes(1)]);
    }
}
