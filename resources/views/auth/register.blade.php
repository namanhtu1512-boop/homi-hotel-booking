@extends('layouts.app')

@section('title', 'Đăng ký Homi')
@section('banner_tag', 'Tham gia Homi')
@section('banner_title', 'Tạo tài khoản Homi')
@section('banner_subtitle', 'Đăng ký để đặt phòng nhanh hơn, theo dõi đơn đặt phòng và quản lý thông tin cá nhân.')

@section('content')
    <div class="auth-layout">
        <div class="card auth-card">
            <div class="section-kicker">Tài khoản mới</div>
            <h2 class="section-title">Đăng ký</h2>
            <p class="section-desc">Điền thông tin bên dưới để tạo tài khoản khách hàng.</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="form-grid">
                @csrf

                <div class="form-group">
                    <label for="name">Họ tên</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}"
                        placeholder="Nhập họ tên của bạn" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                        placeholder="Nhập email của bạn" required>
                </div>

                <div class="form-group">
                    <label for="phone">Số điện thoại</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                        placeholder="09xxxxxxxx">
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input id="password" type="password" name="password" placeholder="Tối thiểu 8 ký tự" required>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Xác nhận mật khẩu</label>
                    <input id="password_confirmation" type="password" name="password_confirmation"
                        placeholder="Nhập lại mật khẩu" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Đăng ký</button>
            </form>

            <div class="auth-footer">
                Đã có tài khoản?
                <a href="{{ route('login') }}">Đăng nhập</a>
            </div>
        </div>

        <div class="auth-side">
            <div class="section-kicker">Lợi ích</div>
            <h2 class="section-title">Quản lý nhanh, nhìn đẹp, dễ dùng</h2>
            <p class="section-desc">
                Tạo tài khoản để đặt phòng, xem lại lịch sử đặt phòng và cập nhật thông tin cá nhân bất cứ lúc nào.
            </p>

            <div class="auth-features">
                <div class="feature-box">
                    <h4>Đặt phòng nhanh</h4>
                    <p>Lưu sẵn thông tin liên hệ để đặt phòng chỉ trong vài bước.</p>
                </div>

                <div class="feature-box">
                    <h4>Theo dõi đơn đặt phòng</h4>
                    <p>Xem trạng thái đơn, hủy đơn khi cần và theo dõi lịch sử lưu trú.</p>
                </div>

                <div class="feature-box">
                    <h4>Quản lý tài khoản</h4>
                    <p>Cập nhật thông tin cá nhân, đổi mật khẩu ngay trong trang cá nhân.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
