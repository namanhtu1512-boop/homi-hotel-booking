@extends('layouts.app')

@php
    $isEdit = $roomType !== null;
@endphp

@section('title', ($isEdit ? 'Sửa loại phòng' : 'Thêm loại phòng') . ' · Homi')
@section('banner_tag', 'Admin · Room Types')
@section('banner_title', $isEdit ? 'Sửa loại phòng' : 'Thêm loại phòng mới')
@section('banner_subtitle', 'Các trường có dấu * là bắt buộc.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">{{ $isEdit ? 'Cập nhật' : 'Tạo mới' }}</div>
            <h2 class="section-title" style="margin-bottom: 6px;">{{ $isEdit ? $roomType->name : 'Loại phòng mới' }}</h2>
        </div>

        <a href="{{ route('admin.room-types.index') }}" class="btn btn-outline">Quay lại danh sách</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST"
        action="{{ $isEdit ? route('admin.room-types.update', $roomType->id) : route('admin.room-types.store') }}"
        class="form-grid">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="name">Tên loại phòng *</label>
            <input id="name" type="text" name="name" value="{{ old('name', $roomType->name ?? '') }}"
                placeholder="VD: Phòng Deluxe" required>
        </div>

        <div class="form-group">
            <label for="price_per_night">Giá / đêm (VNĐ) *</label>
            <input id="price_per_night" type="number" step="1000" min="0" name="price_per_night"
                value="{{ old('price_per_night', $roomType->price_per_night ?? '') }}" required>
        </div>

        <div class="form-group">
            <label for="capacity">Sức chứa (khách) *</label>
            <input id="capacity" type="number" min="1" name="capacity"
                value="{{ old('capacity', $roomType->capacity ?? '') }}" required>
        </div>

        <div class="form-group">
            <label for="bed_type">Loại giường</label>
            <input id="bed_type" type="text" name="bed_type" value="{{ old('bed_type', $roomType->bed_type ?? '') }}"
                placeholder="VD: 1 giường đôi">
        </div>

        <div class="form-group">
            <label for="area">Diện tích (m²)</label>
            <input id="area" type="number" step="0.1" min="0" name="area" value="{{ old('area', $roomType->area ?? '') }}">
        </div>

        <div class="form-group">
            <label for="total_rooms">Tổng số phòng *</label>
            <input id="total_rooms" type="number" min="1" name="total_rooms"
                value="{{ old('total_rooms', $roomType->total_rooms ?? '') }}" required>
        </div>

        <div class="form-group">
            <label for="description">Mô tả</label>
            <textarea id="description" name="description" rows="4">{{ old('description', $roomType->description ?? '') }}</textarea>
        </div>

        <div class="form-group">
            <label for="images_text">Ảnh phòng (mỗi dòng 1 đường dẫn/URL)</label>
            <textarea id="images_text" name="images_text" rows="3"
                placeholder="rooms/anh1.jpg&#10;rooms/anh2.jpg">{{ old('images_text', $isEdit ? $roomType->images->pluck('path')->implode("\n") : '') }}</textarea>
            @if ($isEdit)
                <p class="section-desc">Lưu ý: nếu nhập ảnh mới ở đây, toàn bộ ảnh cũ sẽ bị thay thế. Để trống nếu không muốn đổi ảnh.</p>
            @endif
        </div>

        <button type="submit" class="btn btn-primary btn-block">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo loại phòng' }}</button>
    </form>
</div>
@endsection
