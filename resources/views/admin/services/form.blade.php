@extends('layouts.admin')

@php
    $isEdit = $service !== null;
@endphp

@section('title', ($isEdit ? 'Sửa dịch vụ' : 'Tạo dịch vụ') . ' · Homi Admin')
@section('page_title', $isEdit ? 'Sửa dịch vụ' : 'Tạo dịch vụ mới')
@section('page_subtitle', 'Các trường có dấu * là bắt buộc.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.services.index') }}" class="btn btn-outline">Quay lại danh sách</a>
    </div>

    <form method="POST"
        action="{{ $isEdit ? route('admin.services.update', $service->id) : route('admin.services.store') }}"
        class="form-grid">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="name">Tên dịch vụ *</label>
            <input id="name" type="text" name="name" value="{{ old('name', $service->name ?? '') }}" required placeholder="VD: Ăn sáng buffet">
        </div>

        <div class="form-group">
            <label for="description">Mô tả</label>
            <textarea id="description" name="description" rows="3">{{ old('description', $service->description ?? '') }}</textarea>
        </div>

        <div class="form-group">
            <label for="price">Giá (VNĐ) *</label>
            <input id="price" type="number" min="0" name="price" value="{{ old('price', $service->price ?? '') }}" required>
        </div>

        <div class="form-group">
            <label for="status">Trạng thái *</label>
            <select id="status" name="status" required>
                <option value="active" @selected(old('status', $service->status ?? 'active') === 'active')>Đang bán</option>
                <option value="hidden" @selected(old('status', $service->status ?? '') === 'hidden')>Ẩn</option>
            </select>
        </div>

        <div class="form-group">
            <label for="available_from">Giờ bắt đầu phục vụ</label>
            <input id="available_from" type="time" name="available_from"
                value="{{ old('available_from', $service?->available_from ? substr($service->available_from, 0, 5) : '') }}">
        </div>

        <div class="form-group">
            <label for="available_until">Giờ kết thúc phục vụ</label>
            <input id="available_until" type="time" name="available_until"
                value="{{ old('available_until', $service?->available_until ? substr($service->available_until, 0, 5) : '') }}">
            <p class="text-xs text-slate-500 dark:text-slate-400" style="margin-top: 4px;">Để trống cả 2 ô nếu dịch vụ phục vụ cả ngày (VD: khăn tắm thêm, giặt ủi). Nếu đặt, khách/nhân viên chỉ thêm được dịch vụ này trong đúng khung giờ.</p>
        </div>

        <button type="submit" class="btn btn-primary btn-block">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo dịch vụ' }}</button>
    </form>
</div>
@endsection
