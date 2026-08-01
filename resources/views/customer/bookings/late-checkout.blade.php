@extends('layouts.app')

@section('title', 'Yêu cầu trả phòng muộn · Homi')
@section('banner_tag', 'Đơn ' . $booking->booking_code)
@section('banner_title', 'Yêu cầu trả phòng muộn')
@section('banner_subtitle', 'Chọn giờ bạn muốn trả phòng, khách sạn sẽ kiểm tra tình trạng phòng và phản hồi.')

@section('content')

@php
    $hotel = \App\Models\HotelInfo::instance();
    $standardTime = substr($hotel->check_out_time ?? '12:00:00', 0, 5);
    $minHours = \App\Services\LateCheckoutRequestService::MIN_HOURS_BEFORE_STANDARD_CHECKOUT;
@endphp

<div class="card auth-card" style="max-width: 640px; margin: 0 auto;">
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="info-list mb-5">
        <div class="info-item">
            <span class="label">Ngày trả phòng</span>
            <span class="value">{{ $booking->check_out->format('d/m/Y') }}</span>
        </div>
        <div class="info-item">
            <span class="label">Giờ trả phòng chuẩn</span>
            <span class="value">{{ $standardTime }}</span>
        </div>
    </div>

    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
        Vui lòng gửi yêu cầu trước giờ trả phòng chuẩn ít nhất {{ $minHours }} giờ để khách sạn kịp kiểm tra
        tình trạng phòng. Phụ phí tính theo số giờ trễ: đến 1 giờ 100.000đ, trên 1-2 giờ 200.000đ,
        trên 2-3 giờ 300.000đ, trên 3-6 giờ 50% giá phòng, trên 6 giờ hoặc sau 18:00 tính 100% giá phòng
        (như thêm 1 đêm). Phụ phí cộng vào hóa đơn phát sinh, chỉ cần thanh toán khi trả phòng.
    </p>

    <form method="POST" action="{{ route('customer.bookings.late-checkout.store', $booking->id) }}" class="form-grid">
        @csrf

        <div class="form-group">
            <label for="requested_checkout_time">Giờ bạn muốn trả phòng</label>
            <input id="requested_checkout_time" type="time" name="requested_checkout_time"
                value="{{ old('requested_checkout_time') }}" required>
        </div>

        <div class="form-group">
            <label for="reason">Lý do (không bắt buộc)</label>
            <textarea id="reason" name="reason" rows="3" placeholder="VD: chuyến bay về muộn...">{{ old('reason') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Gửi yêu cầu trả phòng muộn</button>
    </form>

    <div class="auth-footer">
        <a href="{{ route('customer.bookings.show', $booking->id) }}">← Quay lại đơn đặt phòng</a>
    </div>
</div>
@endsection
