@extends('layouts.admin')

@section('title', ($promotion ? 'Sửa khuyến mãi' : 'Thêm khuyến mãi') . ' · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ $promotion ? '✏️ Sửa khuyến mãi' : '➕ Thêm khuyến mãi mới' }}</h1>
            <p>{{ $promotion ? 'Cập nhật chương trình "' . $promotion->name . '"' : 'Tạo chương trình khuyến mãi hoặc thông báo mới' }}</p>
        </div>
        <div class="admin-page-actions"><a href="{{ route('admin.promotions.index') }}" class="btn btn-outline">← Quay lại danh sách</a></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $promotion ? route('admin.promotions.update', $promotion->id) : route('admin.promotions.store') }}">
                @csrf
                @if ($promotion) @method('PUT') @endif

                <div class="form-group">
                    <label class="form-label">Tên<span class="req">*</span></label>
                    <input class="form-control" name="name" required value="{{ old('name', $promotion->name ?? '') }}" placeholder="VD: Giảm 20% dịp hè 2026">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Loại<span class="req">*</span></label>
                        <select class="form-control" name="type">
                            <option value="promotion" @selected(old('type', $promotion->type ?? 'promotion') === 'promotion')>Khuyến mãi</option>
                            <option value="announcement" @selected(old('type', $promotion->type ?? '') === 'announcement')>Thông báo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Áp dụng cho khách sạn</label>
                        <select class="form-control" name="hotel_id">
                            <option value="">Toàn hệ thống</option>
                            @foreach ($hotels as $hotel)
                                <option value="{{ $hotel->id }}" @selected((int) old('hotel_id', $promotion->hotel_id ?? 0) === $hotel->id)>{{ $hotel->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Ngày bắt đầu</label>
                        <input class="form-control" type="date" name="valid_from" value="{{ old('valid_from', $promotion?->valid_from?->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ngày kết thúc</label>
                        <input class="form-control" type="date" name="valid_to" value="{{ old('valid_to', $promotion?->valid_to?->format('Y-m-d')) }}">
                    </div>
                </div>
                <p class="form-hint" style="margin-top:-.7rem;margin-bottom:1.1rem">Để trống cả hai nếu khuyến mãi không giới hạn thời gian.</p>

                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-control" name="description" placeholder="Nội dung khuyến mãi...">{{ old('description', $promotion->description ?? '') }}</textarea>
                </div>

                <div class="action-row" style="margin-top:1.25rem">
                    <button type="submit" class="btn btn-primary">{{ $promotion ? 'Lưu thay đổi' : 'Tạo khuyến mãi' }}</button>
                    <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline">Hủy</a>
                </div>
            </form>
        </div>
    </div>
@endsection
