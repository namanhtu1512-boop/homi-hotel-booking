@extends('layouts.admin')

@section('title', 'Thanh toán · Homi Admin')
@section('page_title', 'Quản lý thanh toán')
@section('page_subtitle', 'Xem và cập nhật trạng thái thanh toán của các đơn đặt phòng.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">Danh sách</div>
            <h2 class="section-title">{{ $payments->total() }} giao dịch thanh toán</h2>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.payments.index') }}" class="filter-bar">
        <input type="text" name="booking_code" value="{{ $filters['booking_code'] ?? '' }}" placeholder="Tìm theo mã đơn...">

        <input type="text" name="customer_name" value="{{ $filters['customer_name'] ?? '' }}" placeholder="Tìm theo tên khách hàng...">

        <select name="status">
            <option value="" @selected(($filters['status'] ?? '') === '')>Tất cả trạng thái</option>
            @foreach (\App\Enums\PaymentStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-outline">Lọc</button>

        @if (array_filter($filters))
            <a href="{{ route('admin.payments.index') }}" class="btn btn-outline">Xóa lọc</a>
        @endif
    </form>

    @if ($payments->isEmpty())
        <div class="empty-box">Không tìm thấy giao dịch thanh toán nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Phương thức</th>
                        <th>Số tiền</th>
                        <th>Trạng thái đơn</th>
                        <th>Trạng thái thanh toán</th>
                        <th>Đã thanh toán lúc</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr>
                            <td>
                                <a href="{{ route('admin.bookings.show', $payment->booking_id) }}">{{ $payment->booking->booking_code }}</a>
                            </td>
                            <td>{{ $payment->booking->customer_name }}</td>
                            <td>{{ $payment->method->label() }}</td>
                            <td>{{ number_format($payment->amount, 0, ',', '.') }}đ</td>
                            <td><span class="badge {{ $payment->booking->status->badgeClass() }}">{{ $payment->booking->status->label() }}</span></td>
                            <td><span class="badge {{ $payment->status->badgeClass() }}">{{ $payment->status->label() }}</span></td>
                            <td>{{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>
                                <div class="action-row">
                                    @if ($payment->booking->canMarkPaymentAsPaid())
                                        <form method="POST" action="{{ route('admin.payments.update-status', $payment->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="paid">
                                            <button type="submit" class="btn btn-primary btn-sm">Đánh dấu đã thanh toán</button>
                                        </form>
                                    @endif

                                    @if ($payment->status->canTransitionTo(\App\Enums\PaymentStatus::REFUNDED))
                                        <form method="POST" action="{{ route('admin.payments.update-status', $payment->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="refunded">
                                            <button type="submit" class="btn btn-outline btn-sm">Đánh dấu đã hoàn tiền</button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.bookings.show', $payment->booking_id) }}" class="btn btn-outline btn-sm">Xem đơn</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="action-row" style="margin-top: 16px;">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection
