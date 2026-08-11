@extends('layouts.admin')

@php
    $isEdit = $surchargeItem !== null;
@endphp

@section('title', ($isEdit ? 'Sửa mục phụ phí' : 'Tạo mục phụ phí') . ' · Homi Admin')
@section('page_title', $isEdit ? 'Sửa mục phụ phí' : 'Tạo mục phụ phí mới')
@section('page_subtitle', 'Các trường có dấu * là bắt buộc. Nhập giá cố định HOẶC ghi chú khoảng giá — bắt buộc 1 trong 2.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.surcharge-items.index') }}" class="btn btn-outline">Quay lại danh sách</a>
    </div>

    <form method="POST"
        action="{{ $isEdit ? route('admin.surcharge-items.update', $surchargeItem->id) : route('admin.surcharge-items.store') }}"
        class="form-grid">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="name">Tên mục phụ phí *</label>
            <input id="name" type="text" name="name" value="{{ old('name', $surchargeItem->name ?? '') }}" required placeholder="VD: Khăn tắm">
        </div>

        <div class="form-group">
            <label for="category">Phân loại *</label>
            @php $currentCategory = old('category', $surchargeItem?->category?->value ?? 'damage'); @endphp
            <select id="category" name="category" required>
                <option value="damage" @selected($currentCategory === 'damage')>🔴 Hỏng / mất đồ</option>
                <option value="violation" @selected($currentCategory === 'violation')>🟠 Vi phạm quy định</option>
                <option value="cleaning" @selected($currentCategory === 'cleaning')>🟡 Vệ sinh đặc biệt</option>
            </select>
        </div>

        <div class="form-group">
            <label for="group">Nhóm</label>
            <input id="group" type="text" name="group" value="{{ old('group', $surchargeItem->group ?? '') }}" placeholder="VD: Đồ giường, Phòng tắm, Điện tử...">
        </div>

        <div class="form-group">
            <label for="price">Giá cố định (VNĐ)</label>
            <input id="price" type="number" min="0" name="price" value="{{ old('price', $surchargeItem->price ?? '') }}">
        </div>

        <div class="form-group">
            <label for="price_note">Ghi chú khoảng giá</label>
            <input id="price_note" type="text" name="price_note" value="{{ old('price_note', $surchargeItem->price_note ?? '') }}" placeholder="VD: 8.000.000–15.000.000đ (tùy mức độ hư hỏng)">
            <p class="text-xs text-slate-500 dark:text-slate-400" style="margin-top: 4px;">Dùng cho mục giá dao động (hư hỏng thiết bị...) — để trống ô Giá cố định ở trên, nhân viên sẽ tự nhập số tiền khi chọn mục này.</p>
        </div>

        <div class="form-group">
            <label for="status">Trạng thái *</label>
            <select id="status" name="status" required>
                <option value="active" @selected(old('status', $surchargeItem->status ?? 'active') === 'active')>Đang dùng</option>
                <option value="hidden" @selected(old('status', $surchargeItem->status ?? '') === 'hidden')>Ẩn</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-block">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo mục phụ phí' }}</button>
    </form>
</div>
@endsection
