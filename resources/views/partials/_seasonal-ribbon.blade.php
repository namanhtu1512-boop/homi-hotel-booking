{{-- Partial: _seasonal-ribbon
    Props:
        $room          — RoomType
        $seasonalRates — Collection<SeasonalRate> đang active (nullable room_type_id = áp dụng mọi phòng)
    Badge nổi bật đặt ở góc ảnh phòng khi đang có giá theo mùa áp dụng.
--}}
@php
    $rate = ($seasonalRates ?? collect())->first(fn ($r) => $r->room_type_id === null || $r->room_type_id === $room->id);
@endphp

@if ($rate)
    <div class="absolute left-3 top-3 z-10 flex animate-pulse items-center gap-1 rounded-full bg-gradient-to-r {{ $rate->adjustment_value < 0 ? 'from-red-600 to-orange-500' : 'from-amber-600 to-amber-500' }} px-3 py-1.5 text-sm font-extrabold text-white shadow-lg ring-2 ring-white">
        <span>{{ $rate->adjustment_value < 0 ? '🔥' : '📈' }}</span>
        <span>{{ $rate->adjustment_type === 'percent' ? number_format($rate->adjustment_value, 0) . '%' : number_format($rate->adjustment_value, 0, ',', '.') . 'đ' }}</span>
    </div>
@endif
