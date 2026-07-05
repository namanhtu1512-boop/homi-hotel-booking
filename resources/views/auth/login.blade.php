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
                    <input id="password" type="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
            </form>

            <div class="my-4 flex items-center gap-3 text-xs font-semibold text-slate-400">
                <span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>
                HOẶC
                <span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>
            </div>

            <button type="button" disabled title="Sắp ra mắt" class="btn btn-outline btn-block cursor-not-allowed opacity-60">
                <svg class="h-4 w-4" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 6.1 29.5 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.7-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.6 15.9 18.9 13 24 13c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 6.1 29.5 4 24 4c-7.6 0-14.1 4.3-17.4 10.7z"/><path fill="#4CAF50" d="M24 44c5.4 0 10.3-1.8 14.1-5l-6.5-5.5c-2 1.5-4.6 2.5-7.6 2.5-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.8 39.6 16.4 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.2 4.3-4.1 5.7l6.5 5.5C41.5 36 44 30.5 44 24c0-1.3-.1-2.7-.4-3.5z"/></svg>
                Đăng nhập bằng Google (sắp ra mắt)
            </button>

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
