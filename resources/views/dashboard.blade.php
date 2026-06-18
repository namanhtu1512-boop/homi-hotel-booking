@extends('layouts.app')

@section('title', 'Dashboard Homi')
@section('banner_tag', 'Dashboard')
@section('banner_title', 'Xin chào, ' . auth()->user()->name)
@section('banner_subtitle', 'Theo dõi nhanh thông tin tài khoản và truy cập các chức năng quan trọng trong hệ thống
    Homi.')

@section('content')
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Tài khoản hiện tại</div>
            <div class="stat-value">{{ auth()->user()->name }}</div>
            <div class="stat-note">{{ auth()->user()->email }}</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Vai trò</div>
            <div class="stat-value">{{ ucfirst(auth()->user()->role) }}</div>
            <div class="stat-note">Phân quyền theo hệ thống Homi</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Trạng thái</div>
            <div class="stat-value">{{ ucfirst(auth()->user()->status) }}</div>
            <div class="stat-note">Tài khoản đang hoạt động bình thường</div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <div class="section-kicker">Thông tin cá nhân</div>
            <h2 class="section-title">Tóm tắt tài khoản</h2>
            <p class="section-desc">Thông tin cơ bản của tài khoản đang đăng nhập.</p>

            <div class="info-list">
                <div class="info-item">
                    <div class="label">Họ tên</div>
                    <div class="value">{{ auth()->user()->name }}</div>
                </div>

                <div class="info-item">
                    <div class="label">Email</div>
                    <div class="value">{{ auth()->user()->email }}</div>
                </div>

                <div class="info-item">
                    <div class="label">Vai trò</div>
                    <div class="value">
                        <span class="badge badge-blue">{{ auth()->user()->role }}</span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="label">Trạng thái</div>
                    <div class="value">
                        <span class="badge badge-green">{{ auth()->user()->status }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="section-kicker">Truy cập nhanh</div>
            <h2 class="section-title">Chức năng</h2>
            <p class="section-desc">Đi nhanh đến các khu vực cần thao tác thường xuyên.</p>

            <div class="quick-actions">
                @if (in_array(auth()->user()->role, ['admin', 'staff']))
                    <a href="{{ route('admin.hotels.index') }}" class="btn btn-primary">Quản lý khách sạn</a>
                    <a href="{{ route('admin.database') }}" class="btn btn-outline">Xem database cơ bản</a>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-block">Đăng xuất</button>
                </form>
            </div>
        </div>
    </div>
@endsection
