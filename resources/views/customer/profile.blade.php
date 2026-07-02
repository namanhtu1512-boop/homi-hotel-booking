@extends('layouts.app')

@section('title', 'Thông tin cá nhân · Homi')
@section('banner_tag', 'Tài khoản')
@section('banner_title', 'Thông tin cá nhân')
@section('banner_subtitle', 'Xem và cập nhật thông tin tài khoản của bạn.')

@section('content')
<div class="dashboard-grid">

    {{-- Cột trái: form cập nhật --}}
    <div>
        <div class="card">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="section-kicker">Chỉnh sửa thông tin</div>
            <h2 class="section-title">Cập nhật tài khoản</h2>

            <form method="POST" action="{{ route('customer.profile.update') }}" class="form-grid" style="margin-top: 20px;">
                @csrf

                <div class="form-group">
                    <label for="name">Họ tên <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="name" name="name"
                           value="{{ old('name', $user->name) }}"
                           placeholder="Nguyễn Văn A" required>
                </div>

                <div class="form-group">
                    <label for="phone">Số điện thoại</label>
                    <input type="tel" id="phone" name="phone"
                           value="{{ old('phone', $user->phone ?? '') }}"
                           placeholder="09xxxxxxxx">
                </div>

                <div class="form-group">
                    <label for="address">Địa chỉ</label>
                    <input type="text" id="address" name="address"
                           value="{{ old('address', $user->address ?? '') }}"
                           placeholder="123 Đường ABC, Quận 1, TP.HCM">
                </div>

                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </form>
        </div>
    </div>

    {{-- Cột phải: thông tin hiện tại --}}
    <div>
        <div class="card">
            <div class="section-kicker">Tài khoản</div>
            <h3 class="section-title" style="font-size: 20px;">Thông tin hiện tại</h3>

            <div class="info-list">
                <div class="info-item">
                    <span class="label">Họ tên</span>
                    <span class="value">{{ $user->name }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Email</span>
                    <span class="value">{{ $user->email }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Điện thoại</span>
                    <span class="value">{{ $user->phone ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Địa chỉ</span>
                    <span class="value">{{ $user->address ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Vai trò</span>
                    <span class="value"><span class="badge badge-blue">{{ $user->role }}</span></span>
                </div>
                <div class="info-item">
                    <span class="label">Trạng thái</span>
                    <span class="value"><span class="badge badge-green">{{ $user->status }}</span></span>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 22px;">
            <div class="quick-actions">
                <a href="{{ route('customer.dashboard') }}" class="btn btn-outline btn-block">← Về Dashboard</a>
                <a href="{{ route('customer.bookings.index') }}" class="btn btn-outline btn-block">Đơn đặt phòng của tôi</a>
            </div>
        </div>
    </div>
</div>
@endsection
