@extends('layouts.app')

@section('title', 'Không có quyền truy cập · Homi')
@section('banner_tag', 'Lỗi 403')
@section('banner_title', 'Không có quyền truy cập')
@section('banner_subtitle', 'Bạn không có quyền thực hiện thao tác hoặc xem trang này.')

@section('content')
<div class="card">
    <div class="alert alert-danger">{{ $exception->getMessage() ?: 'Bạn không có quyền truy cập trang này.' }}</div>
    <a href="{{ url('/') }}" class="btn btn-primary">Về trang chủ</a>
</div>
@endsection
