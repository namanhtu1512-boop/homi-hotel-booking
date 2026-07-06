<?php

namespace App\Services;

use App\Models\RoomType;
use Carbon\Carbon;

class PricingService
{
    public function __construct(
        private SeasonalRateService $seasonalRateService,
        private HotelInfoService $hotelInfoService,
    ) {}

    /**
     * Tính giá 1 dòng đặt phòng theo từng đêm: giá gốc -> điều chỉnh theo
     * mùa (nếu có rate áp dụng đêm đó) -> phụ thu cuối tuần (thứ Sáu/Bảy,
     * tính trên giá ĐÃ điều chỉnh mùa). Phụ thu trẻ em tính riêng theo
     * hotel_info (toàn khách sạn, không theo từng đêm/loại phòng).
     */
    public function calculate(RoomType $roomType, string $checkIn, string $checkOut, int $quantity, int $children = 0): array
    {
        $in  = Carbon::parse($checkIn);
        $out = Carbon::parse($checkOut);

        $nights = $in->diffInDays($out);

        $seasonalRates = $this->seasonalRateService->ratesForRoomType($roomType->id, $checkIn, $checkOut);
        $hotel         = $this->hotelInfoService->current();

        $breakdown    = [];
        $roomSubtotal = 0.0;
        $cursor       = $in->copy();

        for ($i = 0; $i < $nights; $i++) {
            $basePrice = (float) $roomType->price_per_night;

            $rate = $seasonalRates->first(fn ($r) => $r->appliesTo($roomType->id, $cursor));

            $seasonalAdjustment = $rate
                ? ($rate->adjustment_type === 'percent'
                    ? round($basePrice * ((float) $rate->adjustment_value / 100))
                    : (float) $rate->adjustment_value)
                : 0.0;

            $afterSeasonal = $basePrice + $seasonalAdjustment;

            // Cuối tuần = đêm thứ Sáu/Bảy (dayOfWeekIso: 5=Fri, 6=Sat).
            $isWeekend        = in_array($cursor->dayOfWeekIso, [5, 6], true);
            $weekendSurcharge = $isWeekend
                ? round($afterSeasonal * ((float) $hotel->weekend_surcharge_percent / 100))
                : 0.0;

            $nightlyTotal = $afterSeasonal + $weekendSurcharge;

            $breakdown[] = [
                'date'                => $cursor->toDateString(),
                'base_price'          => $basePrice,
                'seasonal_adjustment' => $seasonalAdjustment,
                'is_weekend'          => $isWeekend,
                'weekend_surcharge'   => $weekendSurcharge,
                'nightly_total'       => $nightlyTotal,
            ];

            $roomSubtotal += $nightlyTotal;
            $cursor->addDay();
        }

        $roomSubtotal  *= $quantity;
        $childSurcharge = (float) $hotel->child_surcharge_per_night * $children * $nights;

        return [
            'nights'            => $nights,
            'unit_price'        => (float) $roomType->price_per_night,
            'quantity'          => $quantity,
            'room_subtotal'     => $roomSubtotal,
            'child_surcharge'   => $childSurcharge,
            'total_price'       => $roomSubtotal + $childSurcharge,
            'nightly_breakdown' => $breakdown,
        ];
    }

    public function nightCount(string $checkIn, string $checkOut): int
    {
        return Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
    }
}
