@if ($combination)
    @php
        $categoryLabels = ['standard' => 'Standard', 'superior' => 'Superior', 'deluxe' => 'Deluxe', 'family' => 'Family', 'suite' => 'Suite'];
    @endphp

    <div class="card mb-5">
        @if ($combination['status'] === 'ok')
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <span class="section-kicker">Gợi ý tổ hợp phòng phù hợp</span>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($combination['rooms'] as $room)
                            <span class="badge badge-blue">{{ $room['quantity'] }} x {{ $room['name'] }} ({{ $room['capacity_each'] }} khách/phòng)</span>
                        @endforeach
                    </div>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Tổng {{ $combination['total_rooms'] }} phòng · sức chứa {{ $combination['total_capacity'] }} khách
                        @if ($combination['excess'] > 0)
                            (dư {{ $combination['excess'] }} chỗ)
                        @endif
                        · giá tạm tính {{ number_format($combination['total_price'], 0, ',', '.') }}đ/đêm
                    </p>
                </div>
                <a href="{{ route('customer.bookings.create', [
                        'check_in'  => $filters['check_in'] ?? null,
                        'check_out' => $filters['check_out'] ?? null,
                        'items'     => collect($combination['rooms'])->map(fn ($r) => [
                            'room_type_id' => $r['room_type_id'],
                            'quantity'     => $r['quantity'],
                        ])->all(),
                    ]) }}" class="btn-primary shrink-0">Đặt tổ hợp này</a>
            </div>
        @elseif ($combination['status'] === 'insufficient_rooms')
            <div class="alert alert-warning !mb-0">
                Chỉ còn <strong>{{ $combination['available'] }}</strong> phòng phù hợp, chưa đủ <strong>{{ $combination['needed'] }}</strong> phòng bạn yêu cầu. Hãy giảm số phòng hoặc đổi ngày lưu trú.
            </div>
        @elseif ($combination['status'] === 'insufficient_capacity')
            <div class="alert alert-warning !mb-0">
                {{ $combination['rooms_used'] }} phòng bạn chọn chỉ chứa tối đa <strong>{{ $combination['max_capacity'] }}</strong> khách, chưa đủ <strong>{{ $combination['needed'] }}</strong> khách. Hãy tăng số phòng hoặc giảm số khách.
            </div>
        @elseif ($combination['status'] === 'no_availability')
            <div class="alert alert-danger !mb-0">Không còn phòng trống trong khoảng ngày bạn chọn.</div>
        @endif

        @if (! empty($combination['alternative_categories']))
            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                <span class="text-slate-500 dark:text-slate-400">Loại phòng khác đang còn đủ chỗ:</span>
                @foreach ($combination['alternative_categories'] as $alt)
                    <a href="{{ route('rooms.index', array_merge(request()->except(['page', 'category']), ['category' => $alt['category']])) }}" class="badge badge-blue">
                        {{ $categoryLabels[$alt['category']] ?? $alt['category'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endif
