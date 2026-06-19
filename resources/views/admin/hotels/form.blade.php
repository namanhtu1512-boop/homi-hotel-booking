@extends('layouts.admin')

@php
    $isEdit = $hotel !== null;
@endphp

@section('title', ($isEdit ? 'Sửa khách sạn' : 'Thêm khách sạn') . ' · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ $isEdit ? '✏️ Sửa khách sạn' : '➕ Thêm khách sạn mới' }}</h1>
            <p>{{ $isEdit ? 'Cập nhật thông tin khách sạn "' . $hotel->name . '"' : 'Các trường có dấu * là bắt buộc' }}</p>
        </div>
        <div class="admin-page-actions"><a href="{{ route('admin.hotels.index') }}" class="btn btn-outline">← Quay lại danh sách</a></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $isEdit ? route('admin.hotels.update', $hotel->id) : route('admin.hotels.store') }}">
                @csrf
                @if ($isEdit) @method('PUT') @endif

                <div class="form-group">
                    <label class="form-label">Tên khách sạn<span class="req">*</span></label>
                    <input class="form-control" name="name" required value="{{ old('name', $hotel->name ?? '') }}" placeholder="VD: Homi Đà Nẵng Hotel">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Thành phố<span class="req">*</span></label>
                        <input class="form-control" name="city" required value="{{ old('city', $hotel->city ?? '') }}" placeholder="VD: Đà Nẵng">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quận/huyện</label>
                        <input class="form-control" name="district" value="{{ old('district', $hotel->district ?? '') }}" placeholder="VD: Hải Châu">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Địa chỉ<span class="req">*</span></label>
                    <input class="form-control" name="address" required value="{{ old('address', $hotel->address ?? '') }}" placeholder="VD: 123 Bạch Đằng, Hải Châu, Đà Nẵng">
                </div>

                <div class="form-group" style="max-width:240px">
                    <label class="form-label">Hạng sao</label>
                    <select class="form-control" name="star_rating">
                        <option value="">Không chọn</option>
                        @for ($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" @selected((int) old('star_rating', $hotel->star_rating ?? 0) === $i)>{{ $i }} sao</option>
                        @endfor
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-control" name="description" placeholder="Mô tả ngắn về khách sạn...">{{ old('description', $hotel->description ?? '') }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Tiện ích</label>
                    <div class="check-grid">
                        @forelse ($amenities as $amenity)
                            <label class="check-item">
                                <input type="checkbox" name="amenity_ids[]" value="{{ $amenity->id }}" @checked(in_array($amenity->id, old('amenity_ids', $selectedAmenityIds)))>
                                {{ $amenity->name }}
                            </label>
                        @empty
                            <span class="text-muted">Chưa có tiện ích nào trong hệ thống.</span>
                        @endforelse
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Ảnh khách sạn</label>
                    <textarea class="form-control" name="images_text" placeholder="Mỗi dòng một đường dẫn ảnh (URL)">{{ old('images_text', $isEdit ? $hotel->images->pluck('path')->implode("\n") : '') }}</textarea>
                    @if ($isEdit)
                        <p class="form-hint">Lưu ý: nếu nhập ảnh mới ở đây, toàn bộ ảnh cũ sẽ bị thay thế. Để trống nếu không muốn đổi ảnh.</p>
                    @endif
                </div>

                <div class="action-row" style="margin-top:1.25rem">
                    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo khách sạn' }}</button>
                    <a href="{{ route('admin.hotels.index') }}" class="btn btn-outline">Hủy</a>
                </div>
            </form>
        </div>
    </div>
@endsection
