@extends('layouts.admin')

@section('title', 'Đơn đặt phòng · Homi Admin')
@section('page_title', 'Quản lý đơn đặt phòng')
@section('page_subtitle', 'Xác nhận, hủy đơn và cập nhật trạng thái thanh toán.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">Danh sách</div>
            <h2 class="section-title">{{ $bookings->total() }} đơn đặt phòng</h2>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.bookings.index') }}" class="filter-bar">
        <input type="text" name="booking_code" value="{{ $filters['booking_code'] ?? '' }}" placeholder="Tìm theo mã đơn...">

        <select name="status">
            <option value="" @selected(($filters['status'] ?? '') === '')>Tất cả trạng thái</option>
            @foreach (\App\Enums\BookingStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>

        <input type="date" name="created_from" value="{{ $filters['created_from'] ?? '' }}" title="Ngày đặt từ">
        <input type="date" name="created_to" value="{{ $filters['created_to'] ?? '' }}" title="Ngày đặt đến">

        <button type="submit" class="btn btn-outline">Lọc</button>

        @if (array_filter($filters))
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline">Xóa lọc</a>
        @endif
    </form>

    @if ($bookings->isEmpty())
        <div class="empty-box">Không tìm thấy đơn đặt phòng nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
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
                            <td><a href="{{ route('admin.bookings.show', $booking->id) }}">{{ $booking->booking_code }}</a></td>
                            <td>{{ $booking->customer_name }}</td>
                            <td>{{ $booking->check_in->format('d/m/Y') }}</td>
                            <td>{{ $booking->check_out->format('d/m/Y') }}</td>
                            <td>{{ number_format($booking->total_amount, 0, ',', '.') }}đ</td>
                            <td><span class="badge {{ $booking->status->badgeClass() }}">{{ $booking->status->label() }}</span></td>
                            <td>
                                @if ($booking->payment)
                                    <span class="badge {{ $booking->payment->status->badgeClass() }}">{{ $booking->payment->status->label() }}</span>
                                @else
                                    <span style="color: var(--muted);">—</span>
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
