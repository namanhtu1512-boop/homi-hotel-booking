@extends('layouts.app')

@section('title', 'Dashboard · Homi')
@section('banner_tag', 'Dashboard')
@section('banner_title', 'Xin chào, ' . auth()->user()->name)
@section('banner_subtitle', 'Tổng quan tài khoản khách hàng.')

@section('content')
    <div class="card">
        <div class="section-kicker">Thông tin cá nhân</div>
        <h2 class="section-title">Tóm tắt tài khoản</h2>

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

        <div class="quick-actions">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline btn-block">Đăng xuất</button>
            </form>
        </div>
    </div>
@endsection
