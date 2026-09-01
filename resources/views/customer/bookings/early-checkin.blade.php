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
        Yêu cầu nhận phòng lúc <strong>{{ $earlyTime }}</strong> (sớm <strong class="text-red-500">{{ $autoApproveMaxHours }} giờ</strong> so với giờ chuẩn)
        được <strong>tự động duyệt ngay</strong>, bạn nhận thông báo được vào phòng sớm ngay lập tức.
        Phụ phí {{ number_format($totalFee, 0, ',', '.') }}đ cộng vào tổng tiền đơn và chỉ cần thanh toán khi trả phòng.
    </p>

    <form method="POST" action="{{ route('customer.bookings.early-checkin.store', $booking->id) }}" class="form-grid">
        @csrf

        <div class="form-group">
            <label>Chọn số phòng muốn nhận sớm</label>
            <div class="space-y-2">
                @foreach ($booking->bookingItems as $item)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                        <span class="text-sm">{{ $item->roomType->name ?? 'Phòng' }} <span class="text-slate-400">(tổng {{ $item->quantity }} phòng)</span></span>
                        <input type="number" name="room_selections[{{ $item->id }}]" class="input" style="width: 90px;"
                            min="0" max="{{ $item->quantity }}"
                            value="{{ old('room_selections.' . $item->id, $item->quantity) }}">
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-slate-400 mt-1.5">Mặc định chọn hết — giảm số lượng nếu chỉ một phần phòng trong dòng cần vào sớm.</p>
        </div>

        <div class="form-group">
            <label for="reason">Lý do (không bắt buộc)</label>
            <textarea id="reason" name="reason" rows="3" placeholder="VD: chuyến bay đến sớm...">{{ old('reason') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Gửi yêu cầu nhận phòng sớm</button>
    </form>

    <p class="text-xs text-red-500 mt-4">
        Muốn nhận phòng sớm hơn {{ $autoApproveMaxHours }} giờ? Vui lòng đến trực tiếp quầy lễ tân khi tới khách sạn,
        nhân viên sẽ tư vấn và xử lý tùy tình trạng phòng trống thực tế lúc đó.
    </p>

    <div class="auth-footer">
        <a href="{{ route('customer.bookings.show', $booking->id) }}">← Quay lại đơn đặt phòng</a>
    </div>
</div>
@endsection
