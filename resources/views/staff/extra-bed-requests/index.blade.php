@extends('layouts.staff')

@section('title', 'Yêu cầu giường phụ · Homi Staff')
@section('page_title', 'Yêu cầu giường phụ')
@section('page_subtitle', 'Đơn thiếu giường phụ lúc đặt phòng — chọn phương án thay khách hoặc chờ khách tự chọn.')

@section('content')
<div class="card" style="margin-bottom: 20px;">
    <div class="section-kicker">Giường phụ đang sử dụng</div>
    <h2 class="section-title" style="font-size: 18px;">Phòng nào đang dùng giường phụ trong khoảng ngày</h2>

    <form method="GET" class="filter-bar">
        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
        <div class="form-group">
            <label for="start_date">Từ ngày</label>
            <input type="date" id="start_date" name="start_date" value="{{ $usage['start_date'] }}">
        </div>
        <div class="form-group">
            <label for="end_date">Đến ngày</label>
            <input type="date" id="end_date" name="end_date" value="{{ $usage['end_date'] }}">
        </div>
        <button type="submit" class="btn btn-outline btn-sm">Xem</button>
    </form>

    <div class="stats-grid" style="margin-bottom: 16px;">
        <div class="stat-card">
            <div class="stat-label">Tổng giường phụ</div>
            <div class="stat-value">{{ $usage['total'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Đang sử dụng (cao điểm)</div>
            <div class="stat-value">{{ $usage['used_peak'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Còn trống (thấp điểm)</div>
            <div class="stat-value">{{ $usage['available_min'] }}</div>
        </div>
    </div>

    @if ($usage['daily']->count() > 1)
        <div class="table-wrapper" style="margin-bottom: 16px;">
            <table class="text-xs">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        @foreach ($usage['daily'] as $day)
                            <th class="px-1 text-center">{{ $day['date']->format('d/m') }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Đang dùng / tổng</td>
                        @foreach ($usage['daily'] as $day)
                            @php
                                $full = $usage['total'] > 0 && $day['used'] >= $usage['total'];
                                $cellClass = $full
                                    ? 'bg-red-100 text-red-700 font-bold dark:bg-red-900/40 dark:text-red-300'
                                    : ($day['used'] > 0 ? 'bg-blue-100 text-blue-700 font-semibold dark:bg-blue-900/40 dark:text-blue-300' : 'text-slate-400');
                            @endphp
                            <td class="px-1 text-center {{ $cellClass }}">{{ $day['used'] }}/{{ $usage['total'] }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    @if ($usage['items']->isEmpty())
        <div class="empty-box">Không có phòng nào dùng giường phụ từ ngày {{ \Carbon\Carbon::parse($usage['start_date'])->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($usage['end_date'])->format('d/m/Y') }}.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Phòng</th>
                        <th>Loại phòng</th>
                        <th>Đơn</th>
                        <th>Khách</th>
                        <th>Ngày ở</th>
                        <th>Số giường phụ</th>
                        <th>Trạng thái đơn</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usage['items'] as $item)
                        <tr>
                            <td>{{ $item->rooms->isNotEmpty() ? $item->rooms->pluck('room_number')->implode(', ') : 'Chưa gán phòng' }}</td>
                            <td>{{ $item->roomType->name ?? '—' }}</td>
                            <td>{{ $item->booking->booking_code }}</td>
                            <td>{{ $item->booking->customer_name }}</td>
                            <td>{{ $item->booking->check_in->format('d/m/Y') }} → {{ $item->booking->check_out->format('d/m/Y') }}</td>
                            <td>{{ $item->extra_beds }}</td>
                            <td><span class="badge {{ $item->booking->status->badgeClass() }}">{{ $item->booking->status->label() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="card">
    <form method="GET" class="filter-bar">
        <input type="hidden" name="start_date" value="{{ $usage['start_date'] }}">
        <input type="hidden" name="end_date" value="{{ $usage['end_date'] }}">
        <select name="status" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Chờ xử lý</option>
            <option value="waitlisted" @selected(($filters['status'] ?? '') === 'waitlisted')>Đang chờ (waitlist)</option>
            <option value="resolved" @selected(($filters['status'] ?? '') === 'resolved')>Đã xử lý</option>
        </select>
    </form>

    @if ($requests->isEmpty())
        <div class="empty-box">Chưa có yêu cầu giường phụ nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Đơn</th>
                        <th>Khách</th>
                        <th>Loại phòng</th>
                        <th>Thiếu</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        <tr>
                            <td>{{ $request->booking->booking_code }}</td>
                            <td>{{ $request->booking->customer_name }}</td>
                            <td>{{ $request->bookingItem->roomType->name ?? '—' }}</td>
                            <td>{{ $request->requested_extra_beds - $request->available_extra_beds }}</td>
                            <td>
                                @php
                                    $statusBadge = ['pending' => 'badge-orange', 'waitlisted' => 'badge-blue', 'resolved' => 'badge-green'][$request->status] ?? 'badge-green';
                                    $statusLabel = ['pending' => 'Chờ xử lý', 'waitlisted' => 'Waitlist', 'resolved' => 'Đã xử lý'][$request->status] ?? $request->status;
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            </td>
                            <td>
                                <a href="{{ route('staff.extra-bed-requests.show', $request->id) }}" class="btn btn-outline btn-sm">Xem</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 16px;">{{ $requests->links() }}</div>
    @endif
</div>
@endsection
