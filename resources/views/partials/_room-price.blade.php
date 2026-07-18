@php
    $seasonalRate = ($seasonalRates ?? collect())->first(fn ($r) => $r->room_type_id === null || $r->room_type_id === $room->id);
    $seasonalPrice = $seasonalRate
        ? $room->price_per_night + ($seasonalRate->adjustment_type === 'percent'
            ? round($room->price_per_night * ((float) $seasonalRate->adjustment_value / 100))
            : (float) $seasonalRate->adjustment_value)
        : null;
@endphp

@if ($seasonalRate)
    <div>
        <span class="mb-1.5 inline-flex animate-pulse items-center gap-1 rounded-full bg-gradient-to-r {{ $seasonalRate->adjustment_value < 0 ? 'from-red-600 to-orange-500' : 'from-amber-600 to-amber-500' }} px-3 py-1 text-xs font-extrabold text-white shadow-md">
            {{ $seasonalRate->adjustment_value < 0 ? '🔥 Giảm giá mùa' : '📈 Phụ thu mùa' }} · {{ $seasonalRate->label }}
            ({{ $seasonalRate->adjustment_type === 'percent' ? number_format($seasonalRate->adjustment_value, 0) . '%' : number_format($seasonalRate->adjustment_value, 0, ',', '.') . 'đ' }})
        </span>
        <div>
            <span class="mr-1.5 text-xs text-slate-400 line-through">{{ number_format($room->price_per_night, 0, ',', '.') }}đ</span>
            <span class="room-card-price text-xl {{ $seasonalRate->adjustment_value < 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($seasonalPrice, 0, ',', '.') }}đ<span class="text-xs font-medium text-slate-400">/đêm</span></span>
        </div>
    </div>
@else
    <span class="room-card-price">{{ number_format($room->price_per_night, 0, ',', '.') }}đ<span class="text-xs font-medium text-slate-400">/đêm</span></span>
@endif
