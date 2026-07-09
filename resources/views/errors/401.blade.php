@extends('layouts.app')

@section('title', 'Chưa đăng nhập · Homi')
@section('banner_tag', 'Lỗi 401')
@section('banner_title', 'Bạn chưa đăng nhập')
@section('banner_subtitle', 'Vui lòng đăng nhập để tiếp tục.')

@section('content')
<div class="card">
    <div class="alert alert-danger">{{ $exception->getMessage() ?: 'Bạn cần đăng nhập để truy cập trang này.' }}</div>
    <a href="{{ route('login') }}" class="btn btn-primary">Đăng nhập</a>
</div>
@endsection
