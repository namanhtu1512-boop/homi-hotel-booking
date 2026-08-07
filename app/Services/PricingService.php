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
     * tính trên giá ĐÃ điều chỉnh mùa). Phụ thu trẻ em/giường phụ tính riêng
     * theo hotel_info (toàn khách sạn, không theo từng đêm/loại phòng).
     */
    public function calculate(RoomType $roomType, string $checkIn, string $checkOut, int $quantity, int $children = 0, int $extraBeds = 0): array
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
                'seasonal_rate'       => $rate,
                'is_weekend'          => $isWeekend,
                'weekend_surcharge'   => $weekendSurcharge,
                'nightly_total'       => $nightlyTotal,
            ];

            $roomSubtotal += $nightlyTotal;
            $cursor->addDay();
        }

        $roomSubtotal  *= $quantity;
        $childSurcharge = (float) $hotel->child_surcharge_per_night * $children * $nights;
        $extraBedSurcharge = (float) $hotel->extra_bed_surcharge_per_night * $extraBeds * $nights;

        return [
            'nights'              => $nights,
            'unit_price'          => (float) $roomType->price_per_night,
            'quantity'            => $quantity,
            'room_subtotal'       => $roomSubtotal,
            'child_surcharge'     => $childSurcharge,
            'extra_bed_surcharge' => $extraBedSurcharge,
            'total_price'         => $roomSubtotal + $childSurcharge + $extraBedSurcharge,
            'nightly_breakdown'   => $breakdown,
        ];
    }

    public function nightCount(string $checkIn, string $checkOut): int
    {
        return Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
    }

    /**
     * Xem trước giá "tối nay" (1 đêm, tính từ hôm nay theo giờ Việt Nam) cho
     * 1 loại phòng — dùng cho trang danh sách/chi tiết phòng và ước tính JS
     * lúc đặt phòng, TRƯỚC khi khách chọn ngày nhận/trả cụ thể. Gọi thẳng
     * calculate() nên luôn khớp 100% với giá thật lúc đặt (gồm cả phụ thu
     * cuối tuần + đúng rate mùa nào thắng), khác với việc tự viết lại công
     * thức riêng ở nơi khác rồi bị lệch dần theo thời gian.
     *
     * @return array{base_price: float, preview_price: float, seasonal_rate: ?\App\Models\SeasonalRate, is_discount: bool}
     */
    public function previewTonight(RoomType $roomType): array
    {
        $checkIn  = now('Asia/Ho_Chi_Minh')->toDateString();
        $checkOut = now('Asia/Ho_Chi_Minh')->addDay()->toDateString();

        $pricing = $this->calculate($roomType, $checkIn, $checkOut, 1, 0);
        $night   = $pricing['nightly_breakdown'][0] ?? null;

        $basePrice    = (float) $roomType->price_per_night;
        $previewPrice = $pricing['total_price'];

        return [
            'base_price'    => $basePrice,
            'preview_price' => $previewPrice,
            'seasonal_rate' => $night['seasonal_rate'] ?? null,
            // Chỉ coi là "đang giảm giá" khi giá xem trước THẤP HƠN giá gốc
            // — một seasonal rate dương (phụ thu mùa cao điểm) hoặc phụ thu
            // cuối tuần có thể khiến giá xem trước CAO HƠN, không được vẽ
            // thành nhãn giảm giá màu đỏ trong trường hợp đó.
            'is_discount'   => $previewPrice < $basePrice,
        ];
    }
}
