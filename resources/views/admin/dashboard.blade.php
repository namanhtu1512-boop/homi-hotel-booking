@extends('layouts.admin')

@section('title', 'Dashboard · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div><h1>Dashboard</h1><p>Tổng quan hệ thống · {{ now()->format('d/m/Y') }}</p></div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card blue reveal delay-1">
            <div class="kpi-icon">💰</div>
            <div class="kpi-num">{{ number_format($stats['total_revenue'] / 1000000, 1) }}M đ</div>
            <div class="kpi-label">Doanh thu (không tính đơn hủy)</div>
        </div>
        <div class="kpi-card green reveal delay-2">
            <div class="kpi-icon">📋</div>
            <div class="kpi-num">{{ $stats['total_bookings'] }}</div>
            <div class="kpi-label">Tổng đặt phòng · {{ $stats['bookings_today'] }} hôm nay</div>
        </div>
        <div class="kpi-card orange reveal delay-3">
            <div class="kpi-icon">🏨</div>
            <div class="kpi-num">{{ $stats['total_hotels'] }}</div>
            <div class="kpi-label">Khách sạn đang hoạt động</div>
        </div>
        <div class="kpi-card red reveal delay-4">
            <div class="kpi-icon">👥</div>
            <div class="kpi-num">{{ $stats['total_customers'] }}</div>
            <div class="kpi-label">Khách hàng</div>
        </div>
    </div>

    <div class="quick-actions reveal">
        <a href="{{ route('admin.hotels.create') }}" class="qa-btn"><div class="icon">➕🏨</div><p>Thêm khách sạn</p></a>
        <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}" class="qa-btn"><div class="icon">⏳📋</div><p>Đơn chờ duyệt ({{ $stats['pending_bookings'] }})</p></a>
        <a href="{{ route('admin.users.index') }}" class="qa-btn"><div class="icon">👥</div><p>Quản lý người dùng</p></a>
        <a href="{{ route('admin.room-types.create') }}" class="qa-btn"><div class="icon">➕🛏️</div><p>Thêm loại phòng</p></a>
    </div>

    <div class="chart-row reveal">
        <div class="chart-card">
            <div class="chart-header">
                <h3>📈 Số đặt phòng 7 ngày gần đây</h3>
            </div>
            <div class="chart-area" id="barChart"></div>
            <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--muted);margin-top:.4rem;padding:0 .25rem">
                @foreach ($chartLabels as $label)
                    <span>{{ $label }}</span>
                @endforeach
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-header"><h3>🍩 Trạng thái đặt phòng</h3></div>
            <div class="donut-wrap">
                <div class="donut" id="donutChart"></div>
                <div class="donut-legend" id="donutLegend"></div>
            </div>
        </div>
    </div>

    <div class="data-card reveal">
        <div class="data-card-header">
            📋 Đặt phòng gần đây
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary btn-sm">Xem tất cả →</a>
        </div>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Mã</th><th>Khách hàng</th><th>Khách sạn</th><th>Check-in</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                    @forelse ($recentBookings as $booking)
                        <tr>
                            <td><strong style="color:var(--blue)">{{ $booking->booking_code }}</strong></td>
                            <td>{{ $booking->customer_name }}</td>
                            <td>{{ $booking->hotel->name ?? '—' }}</td>
                            <td>{{ $booking->check_in->format('d/m/Y') }}</td>
                            <td>{{ number_format($booking->total_amount) }}đ</td>
                            <td><span class="badge badge-{{ $booking->status->value }}">{{ $booking->status->label() }}</span></td>
                            <td>
                                @if ($booking->status === \App\Enums\BookingStatus::PENDING)
                                    <form method="POST" action="{{ route('admin.bookings.confirm', $booking) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-primary btn-sm">Duyệt</button>
                                    </form>
                                @else
                                    <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-ghost btn-sm">Xem</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state"><div class="icon">📋</div><h3>Chưa có đặt phòng nào</h3></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="data-card reveal">
        <div class="data-card-header">🏆 Khách sạn doanh thu cao nhất</div>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>#</th><th>Khách sạn</th><th>Đặt phòng</th><th>Doanh thu</th></tr></thead>
                <tbody>
                    @forelse ($topHotels as $i => $hotel)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><div class="entity-cell"><div class="entity-icon">🏨</div>{{ $hotel->name }}</div></td>
                            <td>{{ $hotel->bookings_count }}</td>
                            <td style="font-weight:700;color:var(--blue)">{{ number_format($hotel->revenue ?? 0) }}đ</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state"><div class="icon">🏨</div><h3>Chưa có dữ liệu</h3></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window.__dashboardData = {
        chartValues: @json($chartValues),
        chartLabels: @json($chartLabels),
        donutSegments: @json($donutSegments),
    };
</script>
@endpush
