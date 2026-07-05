@extends('layouts.admin')

@php
    $isEdit = $banner !== null;
@endphp

@section('title', ($isEdit ? 'Sửa banner' : 'Thêm banner') . ' · Homi Admin')
@section('page_title', $isEdit ? 'Sửa banner' : 'Thêm banner mới')
@section('page_subtitle', 'Kích thước khuyến nghị 1600x600px.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.banners.index') }}" class="btn btn-outline">Quay lại danh sách</a>
    </div>

    <form method="POST"
        action="{{ $isEdit ? route('admin.banners.update', $banner->id) : route('admin.banners.store') }}"
        class="form-grid" enctype="multipart/form-data">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="title">Tiêu đề *</label>
            <input id="title" type="text" name="title" value="{{ old('title', $banner->title ?? '') }}" required>
        </div>

        <div class="form-group">
            <label for="subtitle">Phụ đề</label>
            <input id="subtitle" type="text" name="subtitle" value="{{ old('subtitle', $banner->subtitle ?? '') }}">
        </div>

        <div class="form-group">
            <label for="image_file">Tải ảnh lên từ máy {{ $isEdit ? '' : '*' }}</label>
            <input id="image_file" type="file" name="image_file" accept="image/*">
            @if ($isEdit)
                <img src="{{ $banner->image_url }}" alt="" style="margin-top: 8px; width: 180px; height: 100px; object-fit: cover; border-radius: 8px;">
            @endif
        </div>

        <div class="form-group">
            <label for="image_url">Hoặc dán URL ảnh</label>
            <input id="image_url" type="text" name="image_url" value="{{ old('image_url') }}" placeholder="https://...">
        </div>

        <div class="form-group">
            <label for="link_url">Liên kết khi bấm vào banner (tuỳ chọn)</label>
            <input id="link_url" type="text" name="link_url" value="{{ old('link_url', $banner->link_url ?? '') }}" placeholder="/rooms">
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="sort_order">Thứ tự hiển thị</label>
                <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $banner->sort_order ?? 0) }}">
            </div>
            <div class="form-group">
                <label for="status">Trạng thái *</label>
                <select id="status" name="status" required>
                    <option value="active" @selected(old('status', $banner->status ?? 'active') === 'active')>Hiển thị</option>
                    <option value="hidden" @selected(old('status', $banner->status ?? '') === 'hidden')>Ẩn</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">{{ $isEdit ? 'Lưu thay đổi' : 'Thêm banner' }}</button>
    </form>
</div>
@endsection
