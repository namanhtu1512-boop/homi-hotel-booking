<?php

namespace App\Services;

use App\Models\RoomType;
use Carbon\Carbon;

class PricingService
{
    /**
     * Calculate total price for a booking.
     * Uses weekend_price for Friday/Saturday nights if set, base_price otherwise.
     */
    public function calculate(RoomType $roomType, string $checkIn, string $checkOut, int $quantity): array
    {
        $in  = Carbon::parse($checkIn);
        $out = Carbon::parse($checkOut);

        $nights    = $in->diffInDays($out);
        $totalPrice = 0;

        for ($i = 0; $i < $nights; $i++) {
            $night     = $in->copy()->addDays($i);
            $isWeekend = in_array($night->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY]);
            $unitPrice = ($isWeekend && $roomType->weekend_price)
                ? $roomType->weekend_price
                : $roomType->base_price;

            $totalPrice += $unitPrice;
        }

        $totalPrice *= $quantity;

        return [
            'nights'      => $nights,
            'unit_price'  => $roomType->base_price,
            'quantity'    => $quantity,
            'total_price' => $totalPrice,
        ];
    }

    public function nightCount(string $checkIn, string $checkOut): int
    {
        return Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
    }
}
