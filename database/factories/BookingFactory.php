<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $checkIn  = now()->addDays(fake()->numberBetween(1, 30))->startOfDay();
        $checkOut = $checkIn->copy()->addDays(fake()->numberBetween(1, 5));

        return [
            'booking_code'    => 'HM' . fake()->unique()->numerify('########'),
            'user_id'         => null,
            'check_in'        => $checkIn->toDateString(),
            'check_out'       => $checkOut->toDateString(),
            'nights'          => $checkIn->diffInDays($checkOut),
            'adults'          => 2,
            'children'        => 0,
            'infants'         => 0,
            'customer_name'   => fake()->name(),
            'customer_email'  => fake()->safeEmail(),
            'customer_phone'  => fake()->numerify('09########'),
            'total_amount'    => 1000000,
            'discount_amount' => 0,
            'status'          => BookingStatus::CONFIRMED,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::CANCELLED]);
    }
}
