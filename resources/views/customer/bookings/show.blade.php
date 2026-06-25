@extends('layouts.app')

@section('title', 'Đơn ' . $booking->booking_code . ' · Homi')
@section('banner_tag', 'Chi tiết đơn')
@section('banner_title', 'Đơn ' . $booking->booking_code)
@section('banner_subtitle', 'Trạng thái: ' . $booking->status->label())

@section('content')
<div class="dashboard-grid">
    <div>
        <div class="card">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="section-kicker">Phòng đã đặt</div>
            <div class="table-wrapper" style="margin-top: 10px;">
                <table>
                    <thead>
                        <tr>
                            <th>Loại phòng</th>
                            <th>Số lượng</th>
                            <th>Giá/đêm</th>
                            <th>Số đêm</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($booking->bookingItems as $item)
                            <tr>
                                <td>{{ $item->roomType->name ?? '—' }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->price_per_night, 0, ',', '.') }}đ</td>
                                <td>{{ $item->nights }}</td>
                                <td>{{ number_format($item->subtotal, 0, ',', '.') }}đ</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="info-list" style="margin-top: 20px;">
                <div class="info-item">
                    <span class="label">Ngày nhận phòng</span>
                    <span class="value">{{ $booking->check_in->format('d/m/Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Ngày trả phòng</span>
                    <span class="value">{{ $booking->check_out->format('d/m/Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Số đêm</span>
                    <span class="value">{{ $booking->nights }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Tổng tiền</span>
                    <span class="value">{{ number_format($booking->total_amount, 0, ',', '.') }}đ</span>
                </div>
                @if ($booking->note)
                    <div class="info-item">
                        <span class="label">Ghi chú</span>
                        <span class="value">{{ $booking->note }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="section-kicker">Trạng thái</div>
            <div class="info-list" style="margin-top: 10px;">
                <div class="info-item">
                    <span class="label">Đơn hàng</span>
                    <span class="value"><span class="badge badge-blue">{{ $booking->status->label() }}</span></span>
                </div>
                @if ($booking->payment)
                    <div class="info-item">
                        <span class="label">Thanh toán</span>
                        <span class="value">
                            <span class="badge {{ $booking->payment->isPaid() ? 'badge-green' : 'badge-orange' }}">
                                {{ $booking->payment->status->label() }}
                            </span>
                        </span>
                    </div>
                @endif
            </div>

            <div class="section-kicker" style="margin-top: 22px;">Thông tin liên hệ</div>
            <div class="info-list" style="margin-top: 10px;">
                <div class="info-item">
                    <span class="label">Họ tên</span>
                    <span class="value">{{ $booking->customer_name }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Điện thoại</span>
                    <span class="value">{{ $booking->customer_phone }}</span>
                </div>
                @if ($booking->customer_email)
                    <div class="info-item">
                        <span class="label">Email</span>
                        <span class="value">{{ $booking->customer_email }}</span>
                    </div>
                @endif
            </div>

            <div class="quick-actions">
                <a href="{{ route('customer.bookings.index') }}" class="btn btn-outline btn-block">Quay lại danh sách</a>

                @if ($booking->canCancelByCustomer())
                    <form method="POST" action="{{ route('customer.bookings.cancel', $booking->id) }}"
                        onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn {{ $booking->booking_code }}?');">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-block">Hủy đơn</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
