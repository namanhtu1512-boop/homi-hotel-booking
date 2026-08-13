@extends('layouts.admin')

@php
    $isEdit = $policy !== null;
@endphp

@section('title', ($isEdit ? 'Sửa chính sách ưu đãi đoàn' : 'Tạo chính sách ưu đãi đoàn') . ' · Homi Admin')
@section('page_title', $isEdit ? 'Sửa chính sách ưu đãi đoàn' : 'Tạo chính sách ưu đãi đoàn')
@section('page_subtitle', 'Bậc giảm giá theo tổng số phòng trong đơn — bậc cao nhất mà đơn đạt được sẽ được áp dụng.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.group-discount-policies.index') }}" class="btn btn-outline">Quay lại danh sách</a>
    </div>

    <form method="POST"
        action="{{ $isEdit ? route('admin.group-discount-policies.update', $policy->id) : route('admin.group-discount-policies.store') }}"
        class="form-grid">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="name">Tên chính sách</label>
            <input id="name" type="text" name="name" value="{{ old('name', $policy->name ?? '') }}" placeholder="VD: Đoàn từ 10 phòng">
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="min_rooms">Từ số phòng *</label>
                <input id="min_rooms" type="number" min="1" name="min_rooms" value="{{ old('min_rooms', $policy->min_rooms ?? '') }}" required>
            </div>
            <div class="form-group">
                <label for="discount_percent">Giảm giá (%) *</label>
                <input id="discount_percent" type="number" min="0.01" max="100" step="0.1" name="discount_percent" value="{{ old('discount_percent', $policy->discount_percent ?? '') }}" required>
            </div>
        </div>
        <p class="section-desc">Đơn có tổng số phòng ≥ ngưỡng này sẽ tự động được giảm đúng % trên — hệ thống chọn bậc cao nhất mà đơn đạt được, không cộng dồn nhiều bậc.</p>

        <div class="form-group">
            <label for="status">Trạng thái *</label>
            <select id="status" name="status" required>
                <option value="active" @selected(old('status', $policy->status ?? 'active') === 'active')>Đang áp dụng</option>
                <option value="inactive" @selected(old('status', $policy->status ?? '') === 'inactive')>Tắt</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-block">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo chính sách' }}</button>
    </form>
</div>
@endsection
