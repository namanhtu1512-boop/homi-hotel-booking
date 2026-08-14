@extends('layouts.admin')

@section('title', 'Tổng quan · Homi Admin')
@section('page_title', 'Tổng quan hệ thống')
@section('page_subtitle', 'Theo dõi nhanh tình trạng khách sạn, phòng và đơn đặt phòng.')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Trạng thái khách sạn</div>
        <div class="stat-value">
            @if ($stats['hotel_status'] === 'active')
                <span class="badge badge-green">Đang hoạt động</span>
            @else
                <span class="badge badge-orange">Đang bảo trì</span>
            @endif
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Loại phòng</div>
        <div class="stat-value">{{ $stats['active_room_types'] }} / {{ $stats['total_room_types'] }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Khách hàng</div>
        <div class="stat-value">{{ $stats['total_customers'] }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Tổng đơn đặt phòng</div>
        <div class="stat-value">{{ $stats['total_bookings'] }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Đơn chờ xác nhận</div>
        <div class="stat-value">{{ $stats['pending_bookings'] }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Đơn đã xác nhận</div>
        <div class="stat-value">{{ $stats['confirmed_bookings'] }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Đơn đã hủy</div>
        <div class="stat-value">{{ $stats['cancelled_bookings'] }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Tỷ lệ hủy đơn</div>
        <div class="stat-value">{{ $stats['cancellation_rate'] }}%</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Tỷ lệ lấp đầy hôm nay</div>
        <div class="stat-value">{{ $occupancy['rate'] }}%</div>
        <div class="stat-note">{{ $occupancy['occupied'] }}/{{ $occupancy['total'] }} phòng đang có khách</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="section-kicker">Doanh thu</div>
        <h2 class="section-title" style="font-size: 18px;">Doanh thu 6 tháng gần nhất</h2>
        <canvas id="revenue-chart" height="220"></canvas>
    </div>

    <div class="card">
        <div class="section-kicker">Công suất phòng</div>
        <h2 class="section-title" style="font-size: 18px;">Tỷ lệ phòng đã đặt hôm nay</h2>
        <canvas id="occupancy-chart" height="220"></canvas>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    <div class="section-kicker">Thống kê và phân tích tình hình kinh doanh phòng</div>
    <h2 class="section-title" style="font-size: 18px; margin-bottom: 14px;">Bộ lọc thống kê chi tiết</h2>

    <form method="GET" action="{{ route('admin.dashboard') }}" class="filter-bar" style="margin-bottom: 0;">
        <div class="form-group">
            <label for="from">Từ ngày</label>
            <input type="date" id="from" name="from" value="{{ $filters['from'] }}">
        </div>
        <div class="form-group">
            <label for="to">Đến ngày</label>
            <input type="date" id="to" name="to" value="{{ $filters['to'] }}">
        </div>
        <div class="form-group">
            <label for="period">Gộp theo</label>
            <select id="period" name="period">
                <option value="day" @selected($filters['period'] === 'day')>Ngày</option>
                <option value="month" @selected($filters['period'] === 'month')>Tháng</option>
                <option value="year" @selected($filters['period'] === 'year')>Năm</option>
            </select>
        </div>
        <button type="submit" class="btn btn-outline" style="align-self: end;">Áp dụng</button>
    </form>
</div>

<div class="card" style="margin-bottom: 20px;">
    <div class="section-kicker">Thời gian</div>
    <h2 class="section-title" style="font-size: 18px;">Số lượt đặt phòng &amp; doanh thu</h2>
    <div class="stats-grid" style="margin-top: 14px;">
        <div class="stat-card">
            <div class="stat-label">Số lượt đặt phòng</div>
            <div class="stat-value">{{ array_sum($periodStats['bookings']) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Doanh thu trong kỳ</div>
            <div class="stat-value">{{ number_format(array_sum($periodStats['revenue']), 0, ',', '.') }}đ</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Số phòng đã sử dụng</div>
            <div class="stat-value">{{ $roomsUsed }}</div>
            <div class="stat-note">Phòng đã có khách nhận trong kỳ</div>
        </div>
    </div>
    <canvas id="period-chart" height="220"></canvas>
</div>

<div class="card" style="margin-bottom: 20px;">
    <div class="section-kicker">Loại phòng</div>
    <h2 class="section-title" style="font-size: 18px;">Số lượt thuê &amp; doanh thu theo loại phòng</h2>
    <canvas id="room-type-chart" height="180"></canvas>

    @if ($roomTypeStats->isEmpty())
        <div class="empty-box" style="margin-top: 14px;">Chưa có đơn đặt phòng nào trong khoảng đã chọn.</div>
    @else
        <div class="table-wrapper" style="margin-top: 14px;">
            <table>
                <thead>
                    <tr>
                        <th>Loại phòng</th>
                        <th>Số lượt đặt</th>
                        <th>Số lượt thuê (phòng)</th>
                        <th>Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roomTypeStats as $index => $row)
                        <tr>
                            <td>
                                {{ $row->name }}
                                @if ($index === 0)
                                    <span class="badge badge-blue">Phổ biến nhất</span>
                                @endif
                            </td>
                            <td>{{ $row->bookings_count }}</td>
                            <td>{{ $row->rooms_booked }}</td>
                            <td>{{ number_format($row->revenue, 0, ',', '.') }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="dashboard-grid" style="margin-bottom: 20px;">
    <div class="card">
        <div class="section-kicker">Thời gian lưu trú</div>
        <h2 class="section-title" style="font-size: 18px;">Ngắn ngày / dài ngày</h2>
        <div class="stats-grid" style="margin-top: 14px;">
            <div class="stat-card">
                <div class="stat-label">Tổng số đêm khách thuê</div>
                <div class="stat-value">{{ $stayDuration['total_nights'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Thời gian thuê trung bình</div>
                <div class="stat-value">{{ $stayDuration['average_nights'] }} đêm</div>
            </div>
        </div>
        <canvas id="stay-duration-chart" height="220"></canvas>
    </div>

    <div class="card">
        <div class="section-kicker">Tỷ lệ lấp đầy</div>
        <h2 class="section-title" style="font-size: 18px;">Công suất phòng theo kỳ đã lọc</h2>
        <div class="stats-grid" style="margin-top: 14px;">
            <div class="stat-card">
                <div class="stat-label">Tỷ lệ lấp đầy kỳ này</div>
                <div class="stat-value">{{ $occupancyRange['rate'] }}%</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Tỷ lệ phòng còn trống</div>
                <div class="stat-value">{{ 100 - $occupancyRange['rate'] }}%</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Tỷ lệ lấp đầy kỳ trước</div>
                <div class="stat-value">{{ $previousOccupancy['rate'] }}%</div>
                <div class="stat-note">
                    @php $diff = $occupancyRange['rate'] - $previousOccupancy['rate']; @endphp
                    {{ $diff >= 0 ? '+' : '' }}{{ $diff }} điểm % so với kỳ trước liền kề
                </div>
            </div>
        </div>
        <canvas id="occupancy-by-type-chart" height="220"></canvas>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    <div class="section-kicker">Đồ vật / Tiện nghi</div>
    <h2 class="section-title" style="font-size: 18px;">Tiện nghi của từng loại phòng</h2>
    <canvas id="amenity-chart" height="200"></canvas>

    @if ($amenityStats['room_types']->isEmpty())
        <div class="empty-box" style="margin-top: 14px;">Chưa có loại phòng đang hoạt động.</div>
    @else
        <div class="table-wrapper" style="margin-top: 14px;">
            <table>
                <thead>
                    <tr>
                        <th>Loại phòng</th>
                        <th>Số lượng tiện nghi</th>
                        <th>Tiện nghi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($amenityStats['room_types'] as $roomType)
                        <tr>
                            <td>{{ $roomType->name }}</td>
                            <td>{{ $roomType->amenities->count() }}</td>
                            <td>
                                @forelse ($roomType->amenities as $amenity)
                                    <span class="badge badge-gray">{{ $amenity->name }}</span>
                                @empty
                                    <span class="stat-note">Chưa có tiện nghi</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="card">
    <div class="section-kicker">Hoạt động gần đây</div>
    <h2 class="section-title">5 đơn đặt phòng mới nhất</h2>

    @if ($recentBookings->isEmpty())
        <div class="empty-box">Chưa có đơn đặt phòng nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Nhận phòng</th>
                        <th>Trả phòng</th>
                        <th>Trạng thái</th>
                        <th>Tổng tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentBookings as $booking)
                        <tr>
                            <td>{{ $booking->booking_code }}</td>
                            <td>{{ $booking->customer_name }}</td>
                            <td>{{ $booking->check_in->format('d/m/Y') }}</td>
                            <td>{{ $booking->check_out->format('d/m/Y') }}</td>
                            <td>{{ $booking->status->label() }}</td>
                            <td>{{ number_format($booking->total_amount, 0, ',', '.') }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const revenueCtx = document.getElementById('revenue-chart');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: @json($revenue['labels']),
            datasets: [{
                label: 'Doanh thu (đ)',
                data: @json($revenue['totals']),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.12)',
                tension: 0.3,
                fill: true,
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { ticks: { callback: (v) => Number(v).toLocaleString('vi-VN') } } },
        },
    });

    const occupancyCtx = document.getElementById('occupancy-chart');
    new Chart(occupancyCtx, {
        type: 'doughnut',
        data: {
            labels: ['Đã đặt', 'Còn trống'],
            datasets: [{
                data: [{{ $occupancy['occupied'] }}, {{ $occupancy['available'] }}],
                backgroundColor: ['#2563eb', '#e2e8f0'],
            }],
        },
        options: { plugins: { legend: { position: 'bottom' } } },
    });

    const periodCtx = document.getElementById('period-chart');
    new Chart(periodCtx, {
        data: {
            labels: @json($periodStats['labels']),
            datasets: [
                {
                    type: 'bar',
                    label: 'Số lượt đặt phòng',
                    data: @json($periodStats['bookings']),
                    backgroundColor: 'rgba(37,99,235,0.5)',
                    yAxisID: 'y',
                },
                {
                    type: 'line',
                    label: 'Doanh thu (đ)',
                    data: @json($periodStats['revenue']),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245,158,11,0.15)',
                    tension: 0.3,
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: { position: 'left', beginAtZero: true, ticks: { precision: 0 } },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    grid: { drawOnChartArea: false },
                    ticks: { callback: (v) => Number(v).toLocaleString('vi-VN') },
                },
            },
        },
    });

    const stayDurationCtx = document.getElementById('stay-duration-chart');
    new Chart(stayDurationCtx, {
        type: 'doughnut',
        data: {
            labels: ['Ngắn ngày (≤ 2 đêm)', 'Dài ngày (≥ 3 đêm)'],
            datasets: [{
                data: [{{ $stayDuration['short_stay_count'] }}, {{ $stayDuration['long_stay_count'] }}],
                backgroundColor: ['#2563eb', '#10b981'],
            }],
        },
        options: { plugins: { legend: { position: 'bottom' } } },
    });

    const roomTypeCtx = document.getElementById('room-type-chart');
    new Chart(roomTypeCtx, {
        type: 'bar',
        data: {
            labels: @json($roomTypeStats->pluck('name')),
            datasets: [{
                label: 'Số lượt thuê (phòng)',
                data: @json($roomTypeStats->pluck('rooms_booked')),
                backgroundColor: '#2563eb',
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });

    const occupancyByTypeCtx = document.getElementById('occupancy-by-type-chart');
    new Chart(occupancyByTypeCtx, {
        type: 'bar',
        data: {
            labels: @json($occupancyByType->pluck('name')),
            datasets: [{
                label: 'Tỷ lệ lấp đầy (%)',
                data: @json($occupancyByType->pluck('rate')),
                backgroundColor: '#0ea5e9',
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } } },
        },
    });

    const amenityCtx = document.getElementById('amenity-chart');
    new Chart(amenityCtx, {
        type: 'bar',
        data: {
            labels: @json($amenityStats['amenities']->pluck('name')),
            datasets: [{
                label: 'Số loại phòng',
                data: @json($amenityStats['amenities']->pluck('room_types_count')),
                backgroundColor: '#f59e0b',
            }],
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });
})();
</script>
@endpush
@endsection
