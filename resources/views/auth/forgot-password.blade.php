@extends('layouts.app')

@section('title', 'Quên mật khẩu · Homi')
@section('banner_tag', 'Khôi phục tài khoản')
@section('banner_title', 'Quên mật khẩu')
@section('banner_subtitle', 'Nhập email đã đăng ký, chúng tôi sẽ gửi hướng dẫn đặt lại mật khẩu.')

@section('content')
    <div class="auth-layout">
        <div class="card auth-card">
            <div class="section-kicker">Khôi phục mật khẩu</div>
            <h2 class="section-title">Quên mật khẩu</h2>
            <p class="section-desc">Nhập email đã đăng ký, hệ thống sẽ gửi liên kết đặt lại mật khẩu.</p>

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

            <form method="POST" action="{{ route('password.email') }}" class="form-grid">
                @csrf

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                        placeholder="Nhập email đã đăng ký" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Gửi hướng dẫn đặt lại mật khẩu</button>
            </form>

            <div class="auth-footer">
                Đã nhớ mật khẩu?
                <a href="{{ route('login') }}">Đăng nhập</a>
            </div>
        </div>
    </div>
@endsection
