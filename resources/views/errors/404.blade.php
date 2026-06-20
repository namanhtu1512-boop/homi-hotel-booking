@extends('layouts.app')

@section('title', 'Không tìm thấy trang · Homi')
@section('banner_tag', 'Lỗi 404')
@section('banner_title', 'Không tìm thấy trang')
@section('banner_subtitle', 'Trang bạn tìm không tồn tại hoặc đã bị xóa.')

@section('content')
<div class="card">
    <div class="empty-box">Không tìm thấy dữ liệu hoặc đường dẫn bạn yêu cầu.</div>
    <a href="{{ url('/') }}" class="btn btn-primary" style="margin-top: 16px;">Về trang chủ</a>
</div>
@endsection
