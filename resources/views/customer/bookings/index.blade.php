@extends('layouts.app')

@section('title', 'Đơn đặt phòng của tôi · Homi')
@section('banner_tag', 'Đơn của tôi')
@section('banner_title', 'Đơn đặt phòng của tôi')
@section('banner_subtitle', 'Theo dõi trạng thái và lịch sử đặt phòng của bạn.')

@section('content')
<div class="card">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('customer.bookings.index') }}" class="filter-bar">
        <select name="status">
            <option value="">Tất cả trạng thái</option>
            @foreach (\App\Enums\BookingStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary">Lọc</button>
    </form>

    @if ($bookings->isEmpty())
        <div class="empty-box">Bạn chưa có đơn đặt phòng nào. <a href="{{ route('rooms.index') }}">Xem danh sách phòng</a>.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Mã đơn</th>
                        <th>Phòng</th>
                        <th>Nhận / Trả phòng</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Thanh toán</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        @php
                            $cover = $booking->bookingItems->first()?->roomType?->images->first();
                        @endphp
                        <tr>
                            <td>
                                @if ($cover)
                                    <img src="{{ $cover->image_url }}" alt="" style="width: 56px; height: 40px; object-fit: cover; border-radius: 6px;">
                                @else
                                    <span class="badge">Chưa có ảnh</span>
                                @endif
                            </td>
                            <td>{{ $booking->booking_code }}</td>
                            <td>{{ $booking->bookingItems->pluck('roomType.name')->filter()->implode(', ') }}</td>
                            <td>{{ $booking->check_in->format('d/m/Y') }} – {{ $booking->check_out->format('d/m/Y') }}</td>
                            <td>{{ number_format($booking->total_amount, 0, ',', '.') }}đ</td>
                            <td><span class="badge {{ $booking->status->badgeClass() }}">{{ $booking->status->label() }}</span></td>
                            <td>
                                @if ($booking->payment)
                                    <span class="badge {{ $booking->payment->status->badgeClass() }}">
                                        {{ $booking->payment->status->label() }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('customer.bookings.show', $booking->id) }}" class="btn btn-outline btn-sm">Xem chi tiết</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="action-row" style="margin-top: 18px; justify-content: space-between;">
            @if ($bookings->onFirstPage())
                <span class="btn btn-outline btn-sm" style="opacity: .5;">« Trước</span>
            @else
                <a href="{{ $bookings->previousPageUrl() }}" class="btn btn-outline btn-sm">« Trước</a>
            @endif

            <span>Trang {{ $bookings->currentPage() }}/{{ $bookings->lastPage() }}</span>

            @if ($bookings->hasMorePages())
                <a href="{{ $bookings->nextPageUrl() }}" class="btn btn-outline btn-sm">Sau »</a>
            @else
                <span class="btn btn-outline btn-sm" style="opacity: .5;">Sau »</span>
            @endif
        </div>
    @endif
</div>
@endsection
