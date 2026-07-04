@extends('layouts.staff')

@section('title', $roomType->name . ' · Homi Nhân viên')
@section('page_title', $roomType->name)
@section('page_subtitle', 'Chi tiết loại phòng')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            @if ($roomType->status === 'active')
                <span class="badge badge-green">Đang hoạt động</span>
            @elseif ($roomType->status === 'hidden')
                <span class="badge badge-orange">Đang ẩn</span>
            @else
                <span class="badge badge-red">Bảo trì</span>
            @endif
        </div>

        <div class="action-row">
            <a href="{{ route('staff.room-types.edit', $roomType->id) }}" class="btn btn-primary">Sửa</a>
            <a href="{{ route('staff.room-types.index') }}" class="btn btn-outline">Quay lại danh sách</a>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <div class="section-kicker">Mô tả</div>
            <p class="section-desc">{{ $roomType->description ?: 'Chưa có mô tả.' }}</p>
        </div>

        <div>
            <div class="section-kicker">Thông tin phòng</div>
            <p class="section-desc">
                Giá / đêm: {{ number_format($roomType->price_per_night, 0, ',', '.') }}đ<br>
                Sức chứa: {{ $roomType->capacity }} khách<br>
                Loại giường: {{ $roomType->bed_type ?: 'Chưa cập nhật' }}<br>
                Diện tích: {{ $roomType->area ? $roomType->area . ' m²' : 'Chưa cập nhật' }}<br>
                Tổng số phòng: {{ $roomType->total_rooms }}
            </p>
        </div>
    </div>
</div>
@endsection
