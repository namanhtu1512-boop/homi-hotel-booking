@extends('layouts.app')

@section('title', 'Yêu cầu nhận phòng sớm · Homi')
@section('banner_tag', 'Đơn ' . $booking->booking_code)
@section('banner_title', 'Yêu cầu nhận phòng sớm')
@section('banner_subtitle', 'Yêu cầu nhận phòng sớm được tự động duyệt ngay.')

@section('content')

@php
    $hotel = \App\Models\HotelInfo::instance();
    $standardTime = substr($hotel->check_in_time ?? '14:00:00', 0, 5);
    $autoApproveMaxHours = \App\Services\EarlyCheckinRequestService::AUTO_APPROVE_MAX_HOURS;
    $feePerHour = \App\Services\EarlyCheckinRequestService::FEE_PER_HOUR;
    $earlyTime = \Carbon\Carbon::createFromFormat('H:i', $standardTime)
        ->subHours($autoApproveMaxHours)
        ->format('H:i');
    $totalFee = $autoApproveMaxHours * $feePerHour;
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
            <span class="label">Ngày nhận phòng</span>
            <span class="value">{{ $booking->check_in->format('d/m/Y') }}</span>
        </div>
        <div class="info-item">
            <span class="label">Giờ nhận phòng chuẩn</span>
            <span class="value">{{ $standardTime }}</span>
        </div>
    </div>

    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
        Yêu cầu nhận phòng lúc <strong>{{ $earlyTime }}</strong> (sớm {{ $autoApproveMaxHours }} giờ so với giờ chuẩn)
        được <strong>tự động duyệt ngay</strong>, bạn nhận thông báo được vào phòng sớm ngay lập tức.
        Phụ phí {{ number_format($totalFee, 0, ',', '.') }}đ cộng vào tổng tiền đơn và chỉ cần thanh toán khi trả phòng.
    </p>

    <form method="POST" action="{{ route('customer.bookings.early-checkin.store', $booking->id) }}" class="form-grid">
        @csrf

        <div class="form-group">
            <label for="reason">Lý do (không bắt buộc)</label>
            <textarea id="reason" name="reason" rows="3" placeholder="VD: chuyến bay đến sớm...">{{ old('reason') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Gửi yêu cầu nhận phòng sớm</button>
    </form>

    <div class="auth-footer">
        <a href="{{ route('customer.bookings.show', $booking->id) }}">← Quay lại đơn đặt phòng</a>
    </div>
</div>
@endsection
