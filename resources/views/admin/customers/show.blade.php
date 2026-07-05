@extends('layouts.admin')

@section('title', $customer->name . ' · Homi Admin')
@section('page_title', $customer->name)
@section('page_subtitle', 'Thông tin khách hàng và lịch sử đặt phòng.')

@section('content')
<div class="card" style="margin-bottom: 20px;">
    <div class="section-kicker">Thông tin khách hàng</div>
    <div class="table-wrapper">
        <table>
            <tbody>
                <tr>
                    <th style="width: 200px;">Email</th>
                    <td>{{ $customer->email }}</td>
                </tr>
                <tr>
                    <th>Điện thoại</th>
                    <td>{{ $customer->phone ?: '—' }}</td>
                </tr>
                <tr>
                    <th>Địa chỉ</th>
                    <td>{{ $customer->address ?: '—' }}</td>
                </tr>
                <tr>
                    <th>Ngày tạo tài khoản</th>
                    <td>{{ $customer->created_at->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>Trạng thái</th>
                    <td>
                        @if ($customer->status === 'active')
                            <span class="badge badge-green">Đang hoạt động</span>
                        @else
                            <span class="badge badge-red">Đã khóa</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="action-row" style="margin-top: 16px;">
        <a href="{{ route('admin.customers.index') }}" class="btn btn-outline">Quay lại danh sách</a>

        @if ($customer->id !== auth()->id())
            <form method="POST" action="{{ route('admin.users.toggle-status', $customer->id) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline">
                    {{ $customer->status === 'active' ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
                </button>
            </form>
        @endif
    </div>
</div>

<div class="card">
    <div class="section-kicker">Lịch sử đặt phòng</div>
    <h2 class="section-title">{{ $bookings->total() }} đơn đặt phòng</h2>

    @if ($bookings->isEmpty())
        <div class="empty-box">Khách hàng này chưa có đơn đặt phòng nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Loại phòng</th>
                        <th>Ngày đặt</th>
                        <th>Nhận phòng</th>
                        <th>Trả phòng</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Thanh toán</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr>
                            <td>{{ $booking->booking_code }}</td>
                            <td>{{ $booking->bookingItems->pluck('roomType.name')->filter()->implode(', ') ?: '—' }}</td>
                            <td>{{ $booking->created_at->format('d/m/Y') }}</td>
                            <td>{{ $booking->check_in->format('d/m/Y') }}</td>
                            <td>{{ $booking->check_out->format('d/m/Y') }}</td>
                            <td>{{ number_format($booking->total_amount, 0, ',', '.') }}đ</td>
                            <td><span class="badge {{ $booking->status->badgeClass() }}">{{ $booking->status->label() }}</span></td>
                            <td>
                                @if ($booking->payment)
                                    <span class="badge {{ $booking->payment->status->badgeClass() }}">{{ $booking->payment->status->label() }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-outline btn-sm">Xem chi tiết</a>
                            </td>
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
