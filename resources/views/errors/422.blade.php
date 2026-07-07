@extends('layouts.app')

@section('title', 'Dữ liệu không hợp lệ · Homi')
@section('banner_tag', 'Lỗi 422')
@section('banner_title', 'Dữ liệu không hợp lệ')
@section('banner_subtitle', 'Yêu cầu của bạn chứa dữ liệu không hợp lệ, vui lòng kiểm tra lại.')

@section('content')
<div class="card">
    <div class="alert alert-danger">{{ $exception->getMessage() ?: 'Dữ liệu gửi lên không hợp lệ.' }}</div>
    <a href="{{ url()->previous() }}" class="btn btn-primary">Quay lại</a>
</div>
@endsection
