@extends('layouts.app')

@section('title', 'Lỗi máy chủ · Homi')
@section('banner_tag', 'Lỗi 500')
@section('banner_title', 'Đã xảy ra lỗi')
@section('banner_subtitle', 'Lỗi máy chủ. Vui lòng thử lại sau.')

@section('content')
<div class="card">
    <div class="alert alert-danger">Đã xảy ra lỗi không mong muốn. Vui lòng thử lại sau.</div>
    <a href="{{ url('/') }}" class="btn btn-primary">Về trang chủ</a>
</div>
@endsection
