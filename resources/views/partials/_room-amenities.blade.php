{{-- Partial: _room-amenities
    Props:
        $roomType     — RoomType
        $amenityTiers — Collection<['amenity' => Amenity, 'tier' => 'exclusive'|'premium'|'standard']>
                        (RoomTypeService::amenityTiers(), hiếm nhất xếp trước)
--}}
@if ($amenityTiers->isNotEmpty())
    @php
        $exclusive = $amenityTiers->where('tier', 'exclusive');
    @endphp

    <div class="card">
        <span class="section-kicker">Tiện nghi phòng</span>
        <h3 class="mb-1 text-lg font-bold text-slate-900 dark:text-white">Tiện nghi &amp; đặc quyền của {{ $roomType->name }}</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Những gì làm nên sự khác biệt của hạng phòng này.</p>

        @if ($exclusive->isNotEmpty())
            <div class="mb-5 rounded-2xl border border-accent/30 bg-gradient-to-br from-accent-light/70 to-white p-4 dark:border-accent/30 dark:from-accent/10 dark:to-slate-900">
                <div class="mb-3 flex items-center gap-2 text-sm font-bold text-accent-dark dark:text-accent">
                    <span>✨</span> Chỉ riêng {{ $roomType->name }} mới có
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($exclusive as $row)
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-accent-dark/20 bg-white px-3 py-1.5 text-sm font-semibold text-accent-dark shadow-sm dark:border-accent/30 dark:bg-slate-900 dark:text-accent">
                            <span class="text-base">{{ $row['amenity']->icon }}</span> {{ $row['amenity']->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($amenityTiers as $row)
                @php
                    $tier = $row['tier'];
                    $cellClasses = match ($tier) {
                        'exclusive' => 'border-accent/40 bg-accent-light/40 dark:border-accent/30 dark:bg-accent/10',
                        'premium'   => 'border-primary/25 bg-primary-light/40 dark:border-primary/25 dark:bg-primary/10',
                        default     => 'border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/40',
                    };
                @endphp
                <div class="flex flex-col items-center gap-1.5 rounded-xl border {{ $cellClasses }} p-3 text-center">
                    <span class="text-2xl">{{ $row['amenity']->icon }}</span>
                    <span class="text-xs leading-tight font-semibold text-slate-700 dark:text-slate-200">{{ $row['amenity']->name }}</span>
                    @if ($tier === 'exclusive')
                        <span class="badge badge-orange !px-2 !py-0.5 text-[10px]">Độc quyền</span>
                    @elseif ($tier === 'premium')
                        <span class="badge badge-blue !px-2 !py-0.5 text-[10px]">Nâng cấp</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
