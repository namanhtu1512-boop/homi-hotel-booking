@extends('layouts.app')

@section('title', 'Đặt phòng · Homi')
@section('banner_tag', 'Đặt phòng')
@section('banner_title', 'Hoàn tất thông tin đặt phòng')
@section('banner_subtitle', 'Kiểm tra lại thông tin trước khi gửi yêu cầu đặt phòng.')

@section('content')
<div class="card">
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('customer.booking.store') }}" class="form-grid">
        @csrf

        <div class="form-group">
            <label for="room_type_id">Loại phòng</label>
            @if ($roomType)
                <input type="text" value="{{ $roomType->name }} — {{ number_format($roomType->price_per_night, 0, ',', '.') }}đ/đêm" disabled>
                <input type="hidden" name="room_type_id" value="{{ $roomType->id }}">
            @else
                <select id="room_type_id" name="room_type_id" required>
                    <option value="">-- Chọn loại phòng --</option>
                    @foreach ($roomTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('room_type_id') == $type->id)>
                            {{ $type->name }} — {{ number_format($type->price_per_night, 0, ',', '.') }}đ/đêm
                        </option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="form-group">
            <label for="check_in">Ngày nhận phòng</label>
            <input type="date" id="check_in" name="check_in" value="{{ old('check_in', $checkIn) }}" required>
        </div>

        <div class="form-group">
            <label for="check_out">Ngày trả phòng</label>
            <input type="date" id="check_out" name="check_out" value="{{ old('check_out', $checkOut) }}" required>
        </div>

        <div class="form-group">
            <label for="quantity">Số phòng</label>
            <input type="number" id="quantity" name="quantity" min="1" max="10" value="{{ old('quantity', $quantity) }}" required>
        </div>

        <div class="form-group">
            <label for="customer_name">Họ tên khách</label>
            <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', auth()->user()->name) }}" required>
        </div>

        <div class="form-group">
            <label for="customer_phone">Số điện thoại</label>
            <input type="text" id="customer_phone" name="customer_phone" value="{{ old('customer_phone', auth()->user()->phone) }}" required>
        </div>

        <div class="form-group">
            <label for="customer_email">Email liên hệ (tùy chọn)</label>
            <input type="email" id="customer_email" name="customer_email" value="{{ old('customer_email', auth()->user()->email) }}">
        </div>

        <div class="form-group">
            <label for="note">Ghi chú (tùy chọn)</label>
            <textarea id="note" name="note" rows="3">{{ old('note') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Xác nhận đặt phòng</button>
    </form>
</div>
@endsection
