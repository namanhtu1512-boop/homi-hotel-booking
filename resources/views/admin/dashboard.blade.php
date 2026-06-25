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
@endsection
