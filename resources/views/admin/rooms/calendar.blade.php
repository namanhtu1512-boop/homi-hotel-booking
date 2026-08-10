@extends('layouts.admin')

@section('title', 'Lịch phòng · Homi Admin')
@section('page_title', 'Lịch phòng')
@section('page_subtitle', 'Xem theo tháng: phòng nào trả phòng ngày nào, và ngày nào loại phòng nào còn lịch đặt.')

@section('content')
<div class="card">
    <form method="GET" class="filter-bar">
        <input type="month" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()">
        <select name="room_type_id" onchange="this.form.submit()">
            <option value="">Tất cả loại phòng</option>
            @foreach ($roomTypes as $roomType)
                <option value="{{ $roomType->id }}" @selected(($filters['room_type_id'] ?? '') == $roomType->id)>{{ $roomType->name }}</option>
            @endforeach
        </select>
    </form>

    <div class="mb-4 flex flex-wrap gap-3 text-xs">
        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-slate-200 dark:bg-slate-700"></span> Trống</span>
        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-blue-400"></span> Đang ở</span>
        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-amber-400"></span> Trả phòng</span>
        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-red-500"></span> Quá giờ trả phòng</span>
    </div>

    <div class="section-kicker">Theo phòng vật lý — phòng nào trả phòng ngày nào</div>
    @if ($roomRows->isEmpty())
        <div class="empty-box">Chưa có phòng vật lý nào.</div>
    @else
        <div class="table-wrapper mb-6">
            <table class="text-xs">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 bg-white dark:bg-slate-900">Phòng</th>
                        @foreach ($days as $day)
                            <th class="px-1 text-center">{{ $day->day }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roomRows as $row)
                        <tr>
                            <td class="sticky left-0 z-10 bg-white font-semibold whitespace-nowrap dark:bg-slate-900">
                                {{ $row['room']->room_number }}
                                <span class="text-slate-400">— {{ $row['room']->roomType->name ?? '—' }}</span>
                            </td>
                            @foreach ($row['cells'] as $day => $cell)
                                @php
                                    $color = match ($cell['state']) {
                                        'occupied' => 'bg-blue-400',
                                        'checkout' => 'bg-amber-400',
                                        'overdue'  => 'bg-red-500',
                                        default    => 'bg-slate-200 dark:bg-slate-700',
                                    };
                                    $label = match ($cell['state']) {
                                        'occupied' => 'Đang ở',
                                        'checkout' => 'Trả phòng',
                                        'overdue'  => 'Quá giờ trả phòng',
                                        default    => 'Trống',
                                    };
                                    $title = $cell['booking'] ? "{$label} — đơn {$cell['booking']->booking_code}" : $label;
                                @endphp
                                <td class="p-0.5 text-center">
                                    @if ($cell['booking'])
                                        <a href="{{ route('admin.bookings.show', $cell['booking']->id) }}" title="{{ $title }}"
                                           class="inline-block h-4 w-4 rounded {{ $color }}"></a>
                                    @else
                                        <span title="{{ $title }}" class="inline-block h-4 w-4 rounded {{ $color }}"></span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="section-kicker">Theo loại phòng — ngày nào còn lịch đặt</div>
    <p class="mb-2 text-xs text-slate-500 dark:text-slate-400">Số phòng đã có booking / tổng số phòng của loại đó (mọi đơn còn giữ phòng, kể cả chưa nhận phòng).</p>
    @if ($roomTypeRows->isEmpty())
        <div class="empty-box">Chưa có loại phòng nào.</div>
    @else
        <div class="table-wrapper">
            <table class="text-xs">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 bg-white dark:bg-slate-900">Loại phòng</th>
                        @foreach ($days as $day)
                            <th class="px-1 text-center">{{ $day->day }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roomTypeRows as $row)
                        <tr>
                            <td class="sticky left-0 z-10 bg-white font-semibold whitespace-nowrap dark:bg-slate-900">{{ $row['roomType']->name }}</td>
                            @foreach ($row['cells'] as $cell)
                                @php
                                    $full = $cell['total'] > 0 && $cell['booked'] >= $cell['total'];
                                    $textClass = $full ? 'text-red-600 dark:text-red-400 font-bold' : ($cell['booked'] > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400');
                                @endphp
                                <td class="px-1 text-center {{ $textClass }}">{{ $cell['booked'] }}/{{ $cell['total'] }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
