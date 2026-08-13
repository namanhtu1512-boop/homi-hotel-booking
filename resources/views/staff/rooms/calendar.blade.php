@extends('layouts.staff')

@section('title', 'Lịch phòng · Homi Nhân viên')
@section('page_title', 'Lịch phòng')
@section('page_subtitle', 'Chọn khoảng ngày để xem: phòng nào trả phòng ngày nào, và ngày nào loại phòng nào còn lịch đặt. Mặc định hiển thị tháng hiện tại.')

@section('content')
<div class="card">
    <form method="GET" class="filter-bar">
        <select name="room_type_id" onchange="this.form.submit()">
            <option value="">Tất cả loại phòng</option>
            @foreach ($roomTypes as $roomType)
                <option value="{{ $roomType->id }}" @selected(($filters['room_type_id'] ?? '') == $roomType->id)>{{ $roomType->name }}</option>
            @endforeach
        </select>

        <div class="form-group">
            <label for="start_date">Từ ngày</label>
            <input type="date" id="start_date" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
        </div>
        <div class="form-group">
            <label for="end_date">Đến ngày</label>
            <input type="date" id="end_date" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
        </div>

        <button type="submit" class="btn btn-outline">Lọc</button>

        @if (($filters['start_date'] ?? '') !== '' || ($filters['end_date'] ?? '') !== '')
            <a href="{{ route('staff.rooms.calendar', ['room_type_id' => $filters['room_type_id'] ?? null]) }}" class="btn btn-outline">Xóa lọc ngày</a>
        @endif
    </form>
    @if (($filters['start_date'] ?? '') !== '' || ($filters['end_date'] ?? '') !== '')
        <p class="mb-2 text-xs text-slate-500 dark:text-slate-400">Đang lọc theo khoảng ngày{{ ($filters['start_date'] ?? '') !== '' ? ' từ ' . \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') : '' }}{{ ($filters['end_date'] ?? '') !== '' ? ' đến ' . \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') : '' }}.</p>
    @endif

    <div class="mb-4 flex flex-wrap gap-3 text-xs">
        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded border border-slate-300 bg-slate-200 dark:border-slate-600 dark:bg-slate-700"></span> Trống</span>
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
                                        <a href="{{ route('staff.bookings.show', $cell['booking']->id) }}" title="{{ $title }}"
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
    <p class="mb-2 text-xs text-slate-500 dark:text-slate-400">Số phòng đã có booking trong ngày đó (mọi đơn còn giữ phòng, kể cả chưa nhận phòng). Di chuột vào ô để xem tổng số phòng của loại đó.</p>
    <div class="mb-2 flex flex-wrap gap-3 text-xs">
        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded border border-slate-300 dark:border-slate-600"></span> Còn trống, chưa có đặt</span>
        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-blue-100 dark:bg-blue-900/40"></span> Đã có đặt — còn phòng trống</span>
        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-red-100 dark:bg-red-900/40"></span> Hết phòng (đã đặt kín)</span>
    </div>
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
                                    $cellClass = $full
                                        ? 'bg-red-100 text-red-700 font-bold dark:bg-red-900/40 dark:text-red-300'
                                        : ($cell['booked'] > 0
                                            ? 'bg-blue-100 text-blue-700 font-semibold dark:bg-blue-900/40 dark:text-blue-300'
                                            : 'text-slate-400');
                                @endphp
                                <td class="px-1 text-center {{ $cellClass }}" title="{{ $cell['booked'] }}/{{ $cell['total'] }} đã đặt">{{ $cell['booked'] > 0 ? $cell['booked'] : '' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
