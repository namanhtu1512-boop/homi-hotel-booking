@extends('layouts.app')

@section('title', 'Thông tin cá nhân · Homi')
@section('banner_tag', 'Tài khoản')
@section('banner_title', 'Thông tin cá nhân')
@section('banner_subtitle', 'Xem và cập nhật thông tin tài khoản của bạn.')

@section('content')
<div class="grid gap-5 lg:grid-cols-[1.3fr_0.7fr]">

    <div class="space-y-5">
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

            <span class="section-kicker">Chỉnh sửa thông tin</span>
            <h2 class="section-title mb-5">Cập nhật tài khoản</h2>

            <form method="POST" action="{{ route('customer.profile.update') }}" class="space-y-4" enctype="multipart/form-data">
                @csrf

                <div class="flex items-center gap-4">
                    <div class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-full bg-primary-light text-xl font-bold text-primary dark:bg-primary/15">
                        @if ($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" class="h-full w-full object-cover" alt="{{ $user->name }}">
                        @else
                            {{ Str::substr($user->name, 0, 1) }}
                        @endif
                    </div>
                    <div class="flex-1">
                        <label class="form-label" for="avatar">Ảnh đại diện</label>
                        <input class="input" type="file" id="avatar" name="avatar" accept="image/*">
                    </div>
                </div>

                <div>
                    <label class="form-label" for="name">Họ tên <span class="text-red-500">*</span></label>
                    <input class="input" type="text" id="name" name="name" value="{{ old('name', $user->name) }}" placeholder="Nguyễn Văn A" required>
                </div>

                <div>
                    <label class="form-label" for="phone">Số điện thoại</label>
                    <input class="input" type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone ?? '') }}" placeholder="09xxxxxxxx">
                </div>

                <div>
                    <label class="form-label" for="address">Địa chỉ</label>
                    <input class="input" type="text" id="address" name="address" value="{{ old('address', $user->address ?? '') }}" placeholder="123 Đường ABC, Quận 1, TP.HCM">
                </div>

                <button type="submit" class="btn-primary">Lưu thay đổi</button>
            </form>
        </div>

        <div class="card">
            <span class="section-kicker">Bảo mật</span>
            <h3 class="mb-4 text-lg font-bold text-slate-900 dark:text-white">Đổi mật khẩu</h3>

            <form method="POST" action="{{ route('customer.profile.password') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="form-label" for="current_password">Mật khẩu hiện tại</label>
                    <input class="input" type="password" id="current_password" name="current_password" required>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label" for="password">Mật khẩu mới</label>
                        <input class="input" type="password" id="password" name="password" required>
                    </div>
                    <div>
                        <label class="form-label" for="password_confirmation">Xác nhận mật khẩu mới</label>
                        <input class="input" type="password" id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>
                <button type="submit" class="btn-outline">Đổi mật khẩu</button>
            </form>
        </div>

        <div class="card">
            <span class="section-kicker">Bảo mật</span>
            <h3 class="mb-4 text-lg font-bold text-slate-900 dark:text-white">Đổi email</h3>

            <form method="POST" action="{{ route('customer.profile.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="form-label" for="new_email">Email mới</label>
                    <input class="input" type="email" id="new_email" name="email" placeholder="{{ $user->email }}" required>
                </div>
                <div>
                    <label class="form-label" for="email_current_password">Mật khẩu hiện tại (xác nhận)</label>
                    <input class="input" type="password" id="email_current_password" name="current_password" required>
                </div>
                <button type="submit" class="btn-outline">Đổi email</button>
            </form>
        </div>
    </div>

    <div class="h-fit space-y-5">
        <div class="card">
            <span class="section-kicker">Tài khoản</span>
            <h3 class="mb-2 text-lg font-bold text-slate-900 dark:text-white">Thông tin hiện tại</h3>

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

        <div class="card">
            <div class="quick-actions">
                <a href="{{ route('home') }}" class="btn-outline w-full text-center">← Về trang chủ</a>
                <a href="{{ route('customer.bookings.index') }}" class="btn-outline w-full text-center">Đơn đặt phòng của tôi</a>
                <a href="{{ route('customer.wishlist.index') }}" class="btn-outline w-full text-center">Danh sách yêu thích</a>
            </div>
        </div>
    </div>
</div>
@endsection
