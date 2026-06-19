@extends('layouts.admin')

@section('title', 'Thanh toán · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div><h1>💳 Thanh toán</h1><p>Theo dõi và đối soát các giao dịch thanh toán</p></div>
    </div>

    <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
        <div class="kpi-card green">
            <div class="kpi-icon">✅</div>
            <div class="kpi-num">{{ number_format($summary['paid']) }}đ</div>
            <div class="kpi-label">Đã thanh toán</div>
        </div>
        <div class="kpi-card orange">
            <div class="kpi-icon">⏳</div>
            <div class="kpi-num">{{ number_format($summary['unpaid']) }}đ</div>
            <div class="kpi-label">Chưa thanh toán</div>
        </div>
        <div class="kpi-card red">
            <div class="kpi-icon">↩️</div>
            <div class="kpi-num">{{ number_format($summary['refunded']) }}đ</div>
            <div class="kpi-label">Đã hoàn tiền</div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.payments.index') }}" class="admin-toolbar">
        <select class="form-control" name="status" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            @foreach (\App\Enums\PaymentStatus::cases() as $case)
                <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
        <select class="form-control" name="method" onchange="this.form.submit()">
            <option value="">Tất cả phương thức</option>
            @foreach (\App\Enums\PaymentMethod::cases() as $case)
                <option value="{{ $case->value }}" @selected($method === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
        @if ($status || $method)
            <a href="{{ route('admin.payments.index') }}" class="btn btn-ghost">Xóa lọc</a>
        @endif
    </form>

    <div class="data-card">
        <div class="data-card-header">Danh sách giao dịch <span class="count-pill">{{ $payments->total() }}</span></div>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Mã đặt phòng</th><th>Số tiền</th><th>Phương thức</th><th>Trạng thái</th><th>Mã giao dịch</th><th>Ngày thanh toán</th></tr></thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>
                                @if ($payment->booking)
                                    <a href="{{ route('admin.bookings.show', $payment->booking) }}" style="color:var(--blue);font-weight:600">{{ $payment->booking->booking_code }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="font-weight:700">{{ number_format($payment->amount) }}đ</td>
                            <td>{{ $payment->method->label() }}</td>
                            <td><span class="badge {{ $payment->status->value === 'paid' ? 'badge-confirmed' : ($payment->status->value === 'refunded' ? 'badge-cancelled' : 'badge-pending') }}">{{ $payment->status->label() }}</span></td>
                            <td>{{ $payment->transaction_code ?: '—' }}</td>
                            <td>{{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><div class="icon">💳</div><h3>Chưa có giao dịch nào</h3></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials._pagination', ['paginator' => $payments])
    </div>
@endsection
