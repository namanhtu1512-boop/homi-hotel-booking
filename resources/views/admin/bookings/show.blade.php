@extends('layouts.admin')

@section('title', 'Đặt phòng ' . $booking->booking_code . ' · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>📋 Đặt phòng {{ $booking->booking_code }}</h1>
            <p>Tạo lúc {{ $booking->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="admin-page-actions">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline">← Quay lại danh sách</a>
            @if ($booking->canConfirm())
                <form method="POST" action="{{ route('admin.bookings.confirm', $booking) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary">Duyệt đặt phòng</button>
                </form>
            @endif
            @if ($booking->canCancelByAdmin())
                <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}" onsubmit="return confirm('Hủy đặt phòng &quot;{{ $booking->booking_code }}&quot;?');">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-danger">Hủy đặt phòng</button>
                </form>
            @endif
            @if ($booking->canCheckIn())
                <form method="POST" action="{{ route('admin.bookings.check-in', $booking) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary">🛎️ Check-in</button>
                </form>
            @endif
            @if ($booking->canCheckOut())
                <form method="POST" action="{{ route('admin.bookings.check-out', $booking) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-outline">🧳 Check-out</button>
                </form>
            @endif
        </div>
    </div>

    <div class="data-card">
        <div class="data-card-header">Thông tin đặt phòng <span class="badge badge-{{ $booking->status->value }}">{{ $booking->status->label() }}</span></div>
        <div style="padding:1.4rem">
            <div class="info-grid">
                <div class="info-item"><label>Khách hàng</label><p>{{ $booking->customer_name }}</p></div>
                <div class="info-item"><label>Email</label><p>{{ $booking->customer_email }}</p></div>
                <div class="info-item"><label>Điện thoại</label><p>{{ $booking->customer_phone }}</p></div>
                <div class="info-item"><label>Khách sạn</label><p>{{ $booking->hotel->name ?? '—' }}</p></div>
                <div class="info-item"><label>Check-in</label><p>{{ $booking->check_in->format('d/m/Y') }}</p></div>
                <div class="info-item"><label>Check-out</label><p>{{ $booking->check_out->format('d/m/Y') }}</p></div>
                <div class="info-item"><label>Số đêm</label><p>{{ $booking->nights }} đêm</p></div>
                <div class="info-item"><label>Tổng tiền</label><p style="font-weight:700;color:var(--blue)">{{ number_format($booking->total_amount) }}đ</p></div>
            </div>
            @if ($booking->note)
                <hr class="divider">
                <div class="info-item"><label>Ghi chú</label><p>{{ $booking->note }}</p></div>
            @endif
        </div>
    </div>

    <div class="data-card">
        <div class="data-card-header">🛏️ Phòng đã đặt</div>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Loại phòng</th><th>Số lượng</th><th>Giá/đêm</th><th>Số đêm</th><th>Thành tiền</th></tr></thead>
                <tbody>
                    @foreach ($booking->bookingItems as $item)
                        <tr>
                            <td>{{ $item->roomType->name ?? '—' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->price_per_night) }}đ</td>
                            <td>{{ $item->nights }}</td>
                            <td style="font-weight:700">{{ number_format($item->subtotal) }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($booking->payment)
        <div class="data-card">
            <div class="data-card-header">💳 Thanh toán</div>
            <div style="padding:1.4rem">
                <div class="info-grid">
                    <div class="info-item"><label>Số tiền</label><p>{{ number_format($booking->payment->amount) }}đ</p></div>
                    <div class="info-item"><label>Phương thức</label><p>{{ $booking->payment->method?->label() ?? '—' }}</p></div>
                    <div class="info-item"><label>Trạng thái</label><p>{{ $booking->payment->status?->label() ?? '—' }}</p></div>
                </div>
                @if ($booking->payment->method === \App\Enums\PaymentMethod::PAY_AT_HOTEL && ! $booking->payment->status->isPaid())
                    <form method="POST" action="{{ route('admin.payments.confirm-cash', $booking->payment) }}" onsubmit="return confirm('Xác nhận đã thu tiền mặt cho đặt phòng &quot;{{ $booking->booking_code }}&quot;?');" style="margin-top:1rem">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-primary">💵 Xác nhận đã thu tiền mặt</button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if ($booking->statusLogs->isNotEmpty())
        <div class="data-card">
            <div class="data-card-header">🕒 Lịch sử trạng thái</div>
            <div class="table-scroll">
                <table class="table">
                    <thead><tr><th>Thời gian</th><th>Từ</th><th>Đến</th><th>Ghi chú</th></tr></thead>
                    <tbody>
                        @foreach ($booking->statusLogs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $log->from_status ?? '—' }}</td>
                                <td>{{ $log->to_status }}</td>
                                <td>{{ $log->note }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
