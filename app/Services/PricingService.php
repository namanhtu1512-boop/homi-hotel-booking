<?php

namespace App\Services;

use App\Models\RoomType;
use Carbon\Carbon;

class PricingService
{
    public function calculate(RoomType $roomType, string $checkIn, string $checkOut, int $quantity): array
    {
        $in  = Carbon::parse($checkIn);
        $out = Carbon::parse($checkOut);

        $nights     = $in->diffInDays($out);
        $totalPrice = $roomType->price_per_night * $nights * $quantity;

        return [
            'nights'      => $nights,
            'unit_price'  => $roomType->price_per_night,
            'quantity'    => $quantity,
            'total_price' => $totalPrice,
        ];
    }

    public function nightCount(string $checkIn, string $checkOut): int
    {
        return Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
    }
}
