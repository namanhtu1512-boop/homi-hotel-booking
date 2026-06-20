@extends('layouts.app')

@section('title', 'Dashboard Admin · Homi')
@section('banner_tag', 'Admin Dashboard')
@section('banner_title', 'Xin chào, ' . auth()->user()->name)
@section('banner_subtitle', 'Truy cập nhanh các chức năng quản trị hiện có.')

@section('content')
    <div class="card">
        <div class="section-kicker">Truy cập nhanh</div>
        <h2 class="section-title">Quản trị hệ thống</h2>

        <div class="quick-actions" style="grid-template-columns: repeat(3, 1fr); display: grid; gap: 12px;">
            <a href="{{ route('admin.hotel-info.edit') }}" class="btn btn-outline">Thông tin khách sạn</a>
            <a href="{{ route('admin.room-types.index') }}" class="btn btn-outline">Loại phòng</a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Quản lý tài khoản</a>
            <a href="{{ route('admin.database') }}" class="btn btn-outline">Xem database</a>
        </div>
    </div>
@endsection
