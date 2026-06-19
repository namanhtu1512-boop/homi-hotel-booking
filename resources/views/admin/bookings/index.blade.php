@extends('layouts.admin')

@section('title', 'Quản lý đặt phòng · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div><h1>📋 Quản lý đặt phòng</h1><p>Theo dõi, duyệt và xử lý các đơn đặt phòng</p></div>
    </div>

    <form method="GET" action="{{ route('admin.bookings.index') }}" class="admin-toolbar">
        <input class="form-control toolbar-search" type="text" name="search" value="{{ $search }}" placeholder="🔍 Tìm theo mã, khách hàng, email...">
        <select class="form-control" name="status" onchange="this.form.submit()">
            <option value="" @selected($status === '')>Tất cả trạng thái</option>
            @foreach (\App\Enums\BookingStatus::cases() as $case)
                <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline">Lọc</button>
        @if ($search || $status)
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-ghost">Xóa lọc</a>
        @endif
    </form>

    <div class="data-card">
        <div class="data-card-header">Danh sách đặt phòng <span class="count-pill">{{ $bookings->total() }}</span></div>
        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr><th>Mã</th><th>Khách hàng</th><th>Khách sạn</th><th>Check-in → Check-out</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td><strong style="color:var(--blue)">{{ $booking->booking_code }}</strong></td>
                            <td>{{ $booking->customer_name }}<div class="entity-sub">{{ $booking->customer_email }}</div></td>
                            <td>{{ $booking->hotel->name ?? '—' }}</td>
                            <td>{{ $booking->check_in->format('d/m/Y') }} → {{ $booking->check_out->format('d/m/Y') }}</td>
                            <td style="font-weight:700">{{ number_format($booking->total_amount) }}đ</td>
                            <td><span class="badge badge-{{ $booking->status->value }}">{{ $booking->status->label() }}</span></td>
                            <td>
                                <div class="row-actions">
                                    <a class="icon-action" title="Xem chi tiết" href="{{ route('admin.bookings.show', $booking) }}">👁️</a>
                                    @if ($booking->canConfirm())
                                        <form method="POST" action="{{ route('admin.bookings.confirm', $booking) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="icon-action success" title="Duyệt">✔️</button>
                                        </form>
                                    @endif
                                    @if ($booking->canCancelByAdmin())
                                        <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}" onsubmit="return confirm('Hủy đặt phòng &quot;{{ $booking->booking_code }}&quot;?');">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="icon-action danger" title="Hủy đặt phòng">✖️</button>
                                        </form>
                                    @endif
                                    @if ($booking->canCheckIn())
                                        <form method="POST" action="{{ route('admin.bookings.check-in', $booking) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="icon-action success" title="Check-in">🛎️</button>
                                        </form>
                                    @endif
                                    @if ($booking->canCheckOut())
                                        <form method="POST" action="{{ route('admin.bookings.check-out', $booking) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="icon-action" title="Check-out">🧳</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state"><div class="icon">🔍</div><h3>Không tìm thấy đặt phòng</h3><p>Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials._pagination', ['paginator' => $bookings])
    </div>
@endsection
