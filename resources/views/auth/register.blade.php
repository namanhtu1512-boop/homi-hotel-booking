@extends('layouts.app')

@section('title', 'Đăng nhập Homi')
@section('banner_tag', 'Welcome Back')
@section('banner_title', 'Đăng nhập vào hệ thống Homi')
@section('banner_subtitle', 'Truy cập nhanh vào dashboard quản trị, dữ liệu khách sạn và hệ thống đặt phòng với giao
    diện gọn gàng, rõ ràng.')

@section('content')
    <div class="auth-layout">
        <div class="card auth-card">
            <div class="section-kicker">Tài khoản hệ thống</div>
            <h2 class="section-title">Đăng nhập</h2>
            <p class="section-desc">Nhập email và mật khẩu để truy cập hệ thống.</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="form-grid">
                @csrf

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                        placeholder="Nhập email của bạn" required>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input id="password" type="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
            </form>

            <div class="auth-footer">
                Chưa có tài khoản?
                <a href="{{ route('register') }}">Đăng ký ngay</a>
            </div>
        </div>

        <div class="auth-side">
            <div class="section-kicker">Lợi ích</div>
            <h2 class="section-title">Quản lý nhanh, nhìn đẹp, dễ dùng</h2>
            <p class="section-desc">
                Giao diện tông xanh dương và trắng giúp thao tác rõ ràng hơn khi đăng nhập, xem dashboard và theo dõi dữ
                liệu.
            </p>

            <div class="auth-features">
                <div class="feature-box">
                    <h4>Quản lý tài khoản</h4>
                    <p>Đăng nhập, đăng ký và kiểm soát quyền truy cập theo vai trò customer, staff, admin.</p>
                </div>

                <div class="feature-box">
                    <h4>Dashboard rõ ràng</h4>
                    <p>Xem nhanh thông tin tài khoản, vai trò và các chức năng chính ngay sau khi đăng nhập.</p>
                </div>

                <div class="feature-box">
                    <h4>Theo dõi database</h4>
                    <p>Admin và staff có thể xem dữ liệu cơ bản như users, hotels, room_types, bookings, payments.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
