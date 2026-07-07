@extends('layouts.admin')

@section('title', $customer->name . ' · Homi Admin')
@section('page_title', $customer->name)
@section('page_subtitle', $customer->email)

@section('content')
<div class="card">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="page-actions">
        <div>
            <div class="section-kicker">Thông tin khách hàng</div>
            <h2 class="section-title">
                @if ($customer->status === 'active')
                    <span class="badge badge-green">Đang hoạt động</span>
                @else
                    <span class="badge badge-red">Đã khóa</span>
                @endif
            </h2>
        </div>

        <form method="POST" action="{{ route('admin.customers.toggle-status', $customer->id) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-outline">
                {{ $customer->status === 'active' ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
            </button>
        </form>
    </div>

    <div class="table-wrapper" style="margin-top: 8px;">
        <table>
            <tbody>
                <tr><th>Họ tên</th><td>{{ $customer->name }}</td></tr>
                <tr><th>Email</th><td>{{ $customer->email }}</td></tr>
                <tr><th>Số điện thoại</th><td>{{ $customer->phone ?? '—' }}</td></tr>
                <tr><th>Ngày tạo tài khoản</th><td>{{ $customer->created_at->format('d/m/Y') }}</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <div class="page-actions">
        <div>
            <div class="section-kicker">Lịch sử đặt phòng</div>
            <h2 class="section-title">{{ $bookings->total() }} đơn</h2>
        </div>
    </div>

    @if ($bookings->isEmpty())
        <div class="empty-box">Khách hàng chưa có đơn đặt phòng nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Nhận / Trả phòng</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr>
                            <td>{{ $booking->booking_code }}</td>
                            <td>{{ $booking->check_in->format('d/m/Y') }} – {{ $booking->check_out->format('d/m/Y') }}</td>
                            <td>{{ number_format($booking->total_amount, 0, ',', '.') }}đ</td>
                            <td><span class="badge {{ $booking->status->badgeClass() }}">{{ $booking->status->label() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="action-row" style="margin-top: 16px;">
            {{ $bookings->links() }}
        </div>
    @endif
</div>
@endsection
