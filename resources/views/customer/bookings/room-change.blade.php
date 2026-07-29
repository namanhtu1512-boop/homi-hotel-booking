@extends('layouts.app')

@section('title', 'Yêu cầu đổi phòng · Homi')
@section('banner_tag', 'Đơn ' . $booking->booking_code)
@section('banner_title', 'Yêu cầu đổi phòng')
@section('banner_subtitle', 'Chọn loại phòng mới và/hoặc ngày ở mới, khách sạn sẽ xem xét và phản hồi.')

@section('content')

@php
    $item = $booking->bookingItems->first();
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
            <span class="label">Loại phòng hiện tại</span>
            <span class="value">{{ $item?->roomType?->name ?? '—' }}</span>
        </div>
        <div class="info-item">
            <span class="label">Ngày ở hiện tại</span>
            <span class="value">{{ $booking->check_in->format('d/m/Y') }} - {{ $booking->check_out->format('d/m/Y') }}</span>
        </div>
    </div>

    <form method="POST" action="{{ route('customer.bookings.room-change.store', $booking->id) }}" class="form-grid">
        @csrf

        <div class="form-group">
            <label for="requested_room_type_id">Loại phòng mới (để trống nếu giữ nguyên)</label>
            <select id="requested_room_type_id" name="requested_room_type_id">
                <option value="">-- Giữ nguyên loại phòng hiện tại --</option>
                @foreach ($roomTypes as $roomType)
                    <option value="{{ $roomType->id }}" @selected(old('requested_room_type_id') == $roomType->id)>
                        {{ $roomType->name }} ({{ number_format($roomType->price_per_night, 0, ',', '.') }}đ/đêm)
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="requested_check_in">Ngày nhận phòng mới (để trống nếu giữ nguyên)</label>
            <input id="requested_check_in" type="date" name="requested_check_in" value="{{ old('requested_check_in') }}">
        </div>

        <div class="form-group">
            <label for="requested_check_out">Ngày trả phòng mới (để trống nếu giữ nguyên)</label>
            <input id="requested_check_out" type="date" name="requested_check_out" value="{{ old('requested_check_out') }}">
        </div>

        <div class="form-group">
            <label for="reason">Lý do đổi phòng</label>
            <textarea id="reason" name="reason" rows="3" placeholder="VD: cần phòng rộng hơn, đổi ngày công tác...">{{ old('reason') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Gửi yêu cầu đổi phòng</button>
    </form>

    <div class="auth-footer">
        <a href="{{ route('customer.bookings.show', $booking->id) }}">← Quay lại đơn đặt phòng</a>
    </div>
</div>
@endsection
