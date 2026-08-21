@extends('layouts.app')

@section('title', 'Đăng nhập Homi')
@section('banner_tag', 'Chào mừng trở lại')
@section('banner_title', 'Đăng nhập tài khoản Homi')
@section('banner_subtitle', 'Đăng nhập để đặt phòng nhanh hơn, theo dõi đơn đặt phòng và quản lý thông tin cá nhân của bạn.')

@section('content')
    <div class="auth-layout">
        <div class="card auth-card">
            <div class="section-kicker">Tài khoản khách hàng</div>
            <h2 class="section-title">Đăng nhập</h2>
            <p class="section-desc">Nhập email và mật khẩu để tiếp tục đặt phòng.</p>

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
                    <div class="password-field" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" id="password" name="password" placeholder="Nhập mật khẩu" required>
                        <button type="button" class="password-toggle" @click="show = !show" :aria-label="show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'">
                            @include('partials._eye-icon')
                        </button>
                    </div>
                </div>

                <div class="text-right text-sm">
                    <a href="{{ route('password.request') }}">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
            </form>

            <div class="my-4 flex items-center gap-3 text-xs font-semibold text-slate-400">
                <span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>
                HOẶC
                <span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>
            </div>

            @include('auth.partials.social-buttons')

            <div class="auth-footer">
                Chưa có tài khoản?
                <a href="{{ route('register') }}">Đăng ký ngay</a>
            </div>
        </div>

        <div class="auth-side">
            <div class="section-kicker">Lợi ích</div>
            <h2 class="section-title">Đặt phòng nhanh, theo dõi dễ dàng</h2>
            <p class="section-desc">
                Đăng nhập để tiếp tục trải nghiệm đặt phòng liền mạch và quản lý tài khoản của bạn.
            </p>

            <div class="auth-features">
                <div class="feature-box">
                    <h4>Đặt phòng nhanh</h4>
                    <p>Thông tin liên hệ đã lưu sẵn, đặt phòng chỉ trong vài bước.</p>
                </div>

                <div class="feature-box">
                    <h4>Theo dõi đơn đặt phòng</h4>
                    <p>Xem trạng thái đơn, thanh toán, hủy đơn khi cần và theo dõi lịch sử lưu trú.</p>
                </div>

                <div class="feature-box">
                    <h4>Danh sách yêu thích</h4>
                    <p>Lưu lại các loại phòng bạn quan tâm để đặt phòng sau.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
