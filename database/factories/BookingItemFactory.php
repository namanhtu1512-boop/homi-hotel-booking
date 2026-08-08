<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingItemFactory extends Factory
{
    protected $model = BookingItem::class;

    public function definition(): array
    {
        return [
            'booking_id'      => Booking::factory(),
            'room_type_id'    => RoomType::factory(),
            'quantity'        => 1,
            'adults'          => 2,
            'children'        => 0,
            'infants'         => 0,
            'price_per_night' => 1000000,
            'nights'          => 1,
            'subtotal'        => 1000000,
        ];
    }
}
