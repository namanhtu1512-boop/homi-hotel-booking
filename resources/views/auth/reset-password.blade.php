@extends('layouts.app')

@section('title', 'Đặt lại mật khẩu · Homi')
@section('banner_tag', 'Khôi phục tài khoản')
@section('banner_title', 'Đặt lại mật khẩu')
@section('banner_subtitle', 'Nhập mật khẩu mới cho tài khoản của bạn.')

@section('content')
    <div class="auth-layout">
        <div class="card auth-card">
            <div class="section-kicker">Khôi phục mật khẩu</div>
            <h2 class="section-title">Đặt lại mật khẩu</h2>
            <p class="section-desc">Nhập mật khẩu mới, tối thiểu 8 ký tự.</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="form-grid">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}"
                        placeholder="Nhập email của bạn" required>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu mới</label>
                    <input id="password" type="password" name="password" placeholder="Tối thiểu 8 ký tự" required>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Xác nhận mật khẩu mới</label>
                    <input id="password_confirmation" type="password" name="password_confirmation"
                        placeholder="Nhập lại mật khẩu mới" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Đặt lại mật khẩu</button>
            </form>
        </div>
    </div>
@endsection
