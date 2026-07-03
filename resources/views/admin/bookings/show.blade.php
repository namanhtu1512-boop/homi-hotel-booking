@extends('layouts.admin')

@section('title', 'Đơn ' . $booking->booking_code . ' · Homi Admin')
@section('page_title', 'Đơn ' . $booking->booking_code)
@section('page_subtitle', 'Nhận phòng ' . $booking->check_in->format('d/m/Y') . ' · Trả phòng ' . $booking->check_out->format('d/m/Y'))

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <span class="badge {{ $booking->status->badgeClass() }}">{{ $booking->status->label() }}</span>
            @if ($booking->payment)
                <span class="badge {{ $booking->payment->status->badgeClass() }}">{{ $booking->payment->status->label() }}</span>
            @endif
        </div>

        <div class="action-row">
            @if ($booking->canConfirm())
                <form method="POST" action="{{ route('admin.bookings.confirm', $booking->id) }}"
                    onsubmit="return confirm('Xác nhận đơn {{ $booking->booking_code }}?');">
                    @csrf
                    <button type="submit" class="btn btn-primary">Xác nhận đơn</button>
                </form>
            @endif

            @if ($booking->canComplete())
                <form method="POST" action="{{ route('admin.bookings.complete', $booking->id) }}"
                    onsubmit="return confirm('Đánh dấu hoàn thành đơn {{ $booking->booking_code }}?');">
                    @csrf
                    <button type="submit" class="btn btn-primary">Đánh dấu hoàn thành</button>
                </form>
            @endif

            @if ($booking->canCancelByAdmin())
                <form method="POST" action="{{ route('admin.bookings.cancel', $booking->id) }}"
                    onsubmit="return confirm('Hủy đơn {{ $booking->booking_code }}?');">
                    @csrf
                    <button type="submit" class="btn btn-danger">Hủy đơn</button>
                </form>
            @endif

            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline">Quay lại danh sách</a>
        </div>
    </div>

    <div class="section-kicker" style="margin-top: 22px;">Phòng đã đặt</div>
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

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 22px;">
        <div>
            <div class="section-kicker">Thông tin khách hàng</div>
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

        <div>
            <div class="section-kicker">Thanh toán</div>

            @if ($booking->payment)
                <div class="info-list" style="margin-top: 10px;">
                    <div class="info-item">
                        <span class="label">Phương thức</span>
                        <span class="value">{{ $booking->payment->method->label() }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Số tiền</span>
                        <span class="value">{{ number_format($booking->payment->amount, 0, ',', '.') }}đ</span>
                    </div>
                    @if ($booking->payment->paid_at)
                        <div class="info-item">
                            <span class="label">Đã thanh toán lúc</span>
                            <span class="value">{{ $booking->payment->paid_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>

                <div class="action-row" style="margin-top: 16px;">
                    @if ($booking->canMarkPaymentAsPaid())
                        <form method="POST" action="{{ route('admin.bookings.update-payment', $booking->id) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="paid">
                            <button type="submit" class="btn btn-primary btn-sm">Đánh dấu đã thanh toán</button>
                        </form>
                    @endif

                    @if ($booking->payment->status->canTransitionTo(\App\Enums\PaymentStatus::REFUNDED))
                        <form method="POST" action="{{ route('admin.bookings.update-payment', $booking->id) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="refunded">
                            <button type="submit" class="btn btn-outline btn-sm">Đánh dấu đã hoàn tiền</button>
                        </form>
                    @endif
                </div>
            @else
                <div class="empty-box">Đơn này chưa có thông tin thanh toán.</div>
            @endif
        </div>
    </div>

    @if ($booking->statusLogs->isNotEmpty())
        <div class="section-kicker" style="margin-top: 22px;">Lịch sử trạng thái</div>
        <div class="table-wrapper" style="margin-top: 10px;">
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Từ</th>
                        <th>Đến</th>
                        <th>Người thực hiện</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($booking->statusLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->from_status?->label() ?? '—' }}</td>
                            <td>{{ $log->to_status->label() }}</td>
                            <td>{{ $log->changedBy?->name ?? 'Khách hàng' }}</td>
                            <td>{{ $log->note ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($booking->payment && $booking->payment->statusLogs->isNotEmpty())
        <div class="section-kicker" style="margin-top: 22px;">Lịch sử thanh toán</div>
        <div class="table-wrapper" style="margin-top: 10px;">
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Từ</th>
                        <th>Đến</th>
                        <th>Người thực hiện</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($booking->payment->statusLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->from_status?->label() ?? '—' }}</td>
                            <td>{{ $log->to_status->label() }}</td>
                            <td>{{ $log->changedBy?->name ?? 'Khách hàng' }}</td>
                            <td>{{ $log->note ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
