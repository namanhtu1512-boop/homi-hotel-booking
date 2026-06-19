@extends('layouts.admin')

@section('title', ($amenity ? 'Sửa tiện ích' : 'Thêm tiện ích') . ' · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div><h1>{{ $amenity ? '✏️ Sửa tiện ích' : '➕ Thêm tiện ích mới' }}</h1></div>
        <div class="admin-page-actions"><a href="{{ route('admin.amenities.index') }}" class="btn btn-outline">← Quay lại danh sách</a></div>
    </div>

    <div class="card" style="max-width:520px">
        <div class="card-body">
            <form method="POST" action="{{ $amenity ? route('admin.amenities.update', $amenity->id) : route('admin.amenities.store') }}">
                @csrf
                @if ($amenity) @method('PUT') @endif

                <div class="form-group">
                    <label class="form-label">Tên tiện ích<span class="req">*</span></label>
                    <input class="form-control" name="name" required value="{{ old('name', $amenity->name ?? '') }}" placeholder="VD: Hồ bơi">
                </div>

                <div class="form-group">
                    <label class="form-label">Biểu tượng (emoji)</label>
                    <input class="form-control" name="icon" value="{{ old('icon', $amenity->icon ?? '') }}" placeholder="VD: 🏊">
                </div>

                <div class="action-row" style="margin-top:1.25rem">
                    <button type="submit" class="btn btn-primary">{{ $amenity ? 'Lưu thay đổi' : 'Thêm tiện ích' }}</button>
                    <a href="{{ route('admin.amenities.index') }}" class="btn btn-outline">Hủy</a>
                </div>
            </form>
        </div>
    </div>
@endsection
