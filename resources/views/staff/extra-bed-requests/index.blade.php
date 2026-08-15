@extends('layouts.staff')

@section('title', 'Yêu cầu giường phụ · Homi Staff')
@section('page_title', 'Yêu cầu giường phụ')
@section('page_subtitle', 'Đơn thiếu giường phụ lúc đặt phòng — chọn phương án thay khách hoặc chờ khách tự chọn.')

@section('content')
<div class="card" style="margin-bottom: 20px;">
    <div class="section-kicker">Giường phụ đang sử dụng</div>
    <h2 class="section-title" style="font-size: 18px;">Phòng nào đang dùng giường phụ trong ngày</h2>

    <form method="GET" class="filter-bar">
        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
        <input type="date" name="date" value="{{ $usage['date'] }}" onchange="this.form.submit()">
        <button type="submit" class="btn btn-outline btn-sm">Xem</button>
    </form>

    <div class="stats-grid" style="margin-bottom: 16px;">
        <div class="stat-card">
            <div class="stat-label">Tổng giường phụ</div>
            <div class="stat-value">{{ $usage['total'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Đang sử dụng</div>
            <div class="stat-value">{{ $usage['used'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Còn trống</div>
            <div class="stat-value">{{ $usage['available'] }}</div>
        </div>
    </div>

    @if ($usage['items']->isEmpty())
        <div class="empty-box">Không có phòng nào dùng giường phụ trong ngày {{ \Carbon\Carbon::parse($usage['date'])->format('d/m/Y') }}.</div>
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
        <input type="hidden" name="date" value="{{ $usage['date'] }}">
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
