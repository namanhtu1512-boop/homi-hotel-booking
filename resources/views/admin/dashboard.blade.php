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
        <div class="stat-note">Tỷ lệ hủy: {{ $stats['cancellation_rate'] }}%</div>
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
})();
</script>
@endpush
@endsection
