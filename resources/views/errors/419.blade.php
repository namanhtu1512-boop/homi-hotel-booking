@extends('layouts.app')

@section('title', 'Phiên làm việc đã hết hạn · Homi')
@section('banner_tag', 'Lỗi 419')
@section('banner_title', 'Phiên làm việc đã hết hạn')
@section('banner_subtitle', 'Vui lòng tải lại trang và thử lại.')

@section('content')
<div class="card">
    <div class="alert alert-danger">Phiên làm việc của bạn đã hết hạn, vui lòng quay lại và thử lại.</div>
    <a href="{{ url()->previous() }}" class="btn btn-primary">Quay lại</a>
</div>
@endsection
