@extends('layouts.admin')

@php
    $isEdit = $seasonalRate !== null;
@endphp

@section('title', ($isEdit ? 'Sửa đợt giá' : 'Tạo đợt giá') . ' · Homi Admin')
@section('page_title', $isEdit ? 'Sửa đợt giá theo mùa' : 'Tạo đợt giá theo mùa mới')
@section('page_subtitle', 'Các trường có dấu * là bắt buộc.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.seasonal-rates.index') }}" class="btn btn-outline">Quay lại danh sách</a>
    </div>

    <form method="POST"
        action="{{ $isEdit ? route('admin.seasonal-rates.update', $seasonalRate->id) : route('admin.seasonal-rates.store') }}"
        class="form-grid">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="label">Tên đợt giá *</label>
            <input id="label" type="text" name="label" value="{{ old('label', $seasonalRate->label ?? '') }}" required placeholder="VD: Tết Nguyên Đán 2027">
        </div>

        <div class="form-group">
            <label for="room_type_id">Áp dụng cho</label>
            <select id="room_type_id" name="room_type_id">
                <option value="">-- Tất cả loại phòng --</option>
                @foreach ($roomTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) old('room_type_id', $seasonalRate->room_type_id ?? '') === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="start_date">Ngày bắt đầu *</label>
                <input id="start_date" type="date" name="start_date" value="{{ old('start_date', optional($seasonalRate->start_date ?? null)->format('Y-m-d')) }}" required>
            </div>
            <div class="form-group">
                <label for="end_date">Ngày kết thúc *</label>
                <input id="end_date" type="date" name="end_date" value="{{ old('end_date', optional($seasonalRate->end_date ?? null)->format('Y-m-d')) }}" required>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="adjustment_type">Loại điều chỉnh *</label>
                <select id="adjustment_type" name="adjustment_type" required>
                    <option value="percent" @selected(old('adjustment_type', $seasonalRate->adjustment_type ?? 'percent') === 'percent')>Phần trăm (%)</option>
                    <option value="fixed_per_night" @selected(old('adjustment_type', $seasonalRate->adjustment_type ?? '') === 'fixed_per_night')>Số tiền cố định/đêm (VNĐ)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="adjustment_value">Giá trị điều chỉnh *</label>
                <input id="adjustment_value" type="number" step="0.01" name="adjustment_value" value="{{ old('adjustment_value', $seasonalRate->adjustment_value ?? '') }}" required>
            </div>
        </div>
        <p class="section-desc">Nhập số dương để TĂNG giá (mùa cao điểm), số âm để GIẢM giá (mùa thấp điểm). VD: 20 = tăng 20%/+20.000đ, -20 = giảm 20%/-20.000đ.</p>

        <div class="form-group">
            <label for="status">Trạng thái *</label>
            <select id="status" name="status" required>
                <option value="active" @selected(old('status', $seasonalRate->status ?? 'active') === 'active')>Đang áp dụng</option>
                <option value="hidden" @selected(old('status', $seasonalRate->status ?? '') === 'hidden')>Ẩn</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-block">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo đợt giá' }}</button>
    </form>
</div>
@endsection
