<?php

namespace Database\Factories;

use App\Models\GroupBookingRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupBookingRequestFactory extends Factory
{
    protected $model = GroupBookingRequest::class;

    public function definition(): array
    {
        return [
            'user_id'      => null,
            'company_name' => null,
            'contact_name' => fake()->name(),
            'email'        => fake()->safeEmail(),
            'phone'        => fake()->numerify('09########'),
            'group_size'   => 10,
            'num_children' => 0,
            'num_infants'  => 0,
            'room_count'   => 5,
            'check_in'     => now()->addDays(10)->toDateString(),
            'check_out'    => now()->addDays(12)->toDateString(),
            'status'       => 'new',
        ];
    }
}
