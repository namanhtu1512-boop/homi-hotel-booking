@extends('layouts.admin')

@section('title', ($roomType ? 'Sửa loại phòng' : 'Thêm loại phòng') . ' · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ $roomType ? '✏️ Sửa loại phòng' : '➕ Thêm loại phòng mới' }}</h1>
            <p>{{ $roomType ? 'Cập nhật thông tin loại phòng "' . $roomType->name . '"' : 'Tạo loại phòng mới cho một khách sạn' }}</p>
        </div>
        <div class="admin-page-actions"><a href="{{ route('admin.room-types.index') }}" class="btn btn-outline">← Quay lại danh sách</a></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $roomType ? route('admin.room-types.update', $roomType->id) : route('admin.room-types.store') }}">
                @csrf
                @if ($roomType) @method('PUT') @endif

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Khách sạn<span class="req">*</span></label>
                        <select class="form-control" name="hotel_id" {{ $roomType ? 'disabled' : 'required' }}>
                            @foreach ($hotels as $hotel)
                                <option value="{{ $hotel->id }}" @selected((int) $selectedHotelId === $hotel->id)>{{ $hotel->name }}</option>
                            @endforeach
                        </select>
                        @if ($roomType)
                            <input type="hidden" name="hotel_id" value="{{ $roomType->hotel_id }}">
                            <p class="form-hint">Không thể đổi khách sạn của loại phòng đã tạo.</p>
                        @endif
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tên loại phòng<span class="req">*</span></label>
                        <input class="form-control" name="name" required value="{{ old('name', $roomType->name ?? '') }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Giá / đêm (đ)<span class="req">*</span></label>
                        <input class="form-control" type="number" min="0" step="1000" name="price_per_night" required value="{{ old('price_per_night', $roomType->price_per_night ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sức chứa (khách)<span class="req">*</span></label>
                        <input class="form-control" type="number" min="1" name="capacity" required value="{{ old('capacity', $roomType->capacity ?? 2) }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Loại giường</label>
                        <input class="form-control" name="bed_type" value="{{ old('bed_type', $roomType->bed_type ?? '') }}" placeholder="VD: 1 giường đôi">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Diện tích (m²)</label>
                        <input class="form-control" type="number" min="0" step="0.1" name="area" value="{{ old('area', $roomType->area ?? '') }}">
                    </div>
                </div>

                <div class="form-group" style="max-width:240px">
                    <label class="form-label">Tổng số phòng<span class="req">*</span></label>
                    <input class="form-control" type="number" min="1" name="total_rooms" required value="{{ old('total_rooms', $roomType->total_rooms ?? 1) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-control" name="description" placeholder="Mô tả loại phòng...">{{ old('description', $roomType->description ?? '') }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Ảnh phòng</label>
                    <textarea class="form-control" name="images_text" placeholder="Mỗi dòng một đường dẫn ảnh (URL)">{{ old('images_text', $roomType?->images->pluck('path')->implode("\n")) }}</textarea>
                    <p class="form-hint">Mỗi dòng một URL ảnh. Để trống nếu chưa có ảnh.</p>
                </div>

                <div class="action-row" style="margin-top:1.25rem">
                    <button type="submit" class="btn btn-primary">{{ $roomType ? 'Lưu thay đổi' : 'Thêm loại phòng' }}</button>
                    <a href="{{ route('admin.room-types.index') }}" class="btn btn-outline">Hủy</a>
                </div>
            </form>
        </div>
    </div>
@endsection
