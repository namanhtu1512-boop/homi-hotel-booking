@extends('layouts.admin')

@php
    $isEdit = $promotion !== null;
@endphp

@section('title', ($isEdit ? 'Sửa khuyến mãi' : 'Tạo khuyến mãi') . ' · Homi Admin')
@section('page_title', $isEdit ? 'Sửa khuyến mãi' : 'Tạo khuyến mãi mới')
@section('page_subtitle', 'Các trường có dấu * là bắt buộc.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline">Quay lại danh sách</a>
    </div>

    <form method="POST"
        action="{{ $isEdit ? route('admin.promotions.update', $promotion->id) : route('admin.promotions.store') }}"
        class="form-grid">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="name">Tên khuyến mãi *</label>
            <input id="name" type="text" name="name" value="{{ old('name', $promotion->name ?? '') }}" required>
        </div>

        <div class="form-group">
            <label for="code">Mã khuyến mãi *</label>
            <input id="code" type="text" name="code" value="{{ old('code', $promotion->code ?? '') }}" required placeholder="SUMMER2026">
        </div>

        <div class="form-group">
            <label for="description">Mô tả</label>
            <textarea id="description" name="description" rows="3">{{ old('description', $promotion->description ?? '') }}</textarea>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="discount_percent">Giảm theo % (0-100)</label>
                <input id="discount_percent" type="number" min="0" max="100" step="0.1" name="discount_percent" value="{{ old('discount_percent', $promotion->discount_percent ?? '') }}">
            </div>
            <div class="form-group">
                <label for="discount_amount">Hoặc giảm số tiền cố định (VNĐ)</label>
                <input id="discount_amount" type="number" min="0" name="discount_amount" value="{{ old('discount_amount', $promotion->discount_amount ?? '') }}">
            </div>
        </div>
        <p class="section-desc">Chỉ cần điền một trong hai — ưu tiên % nếu điền cả hai.</p>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="starts_at">Ngày bắt đầu</label>
                <input id="starts_at" type="date" name="starts_at" value="{{ old('starts_at', optional($promotion->starts_at ?? null)->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label for="ends_at">Ngày kết thúc</label>
                <input id="ends_at" type="date" name="ends_at" value="{{ old('ends_at', optional($promotion->ends_at ?? null)->format('Y-m-d')) }}">
            </div>
        </div>

        <div class="form-group">
            <label for="status">Trạng thái *</label>
            <select id="status" name="status" required>
                <option value="active" @selected(old('status', $promotion->status ?? 'active') === 'active')>Đang chạy</option>
                <option value="hidden" @selected(old('status', $promotion->status ?? '') === 'hidden')>Ẩn</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-block">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo khuyến mãi' }}</button>
    </form>
</div>
@endsection
