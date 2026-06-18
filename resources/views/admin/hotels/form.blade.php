@extends('layouts.app')

@php
    $isEdit = $hotel !== null;
@endphp

@section('title', ($isEdit ? 'Sửa khách sạn' : 'Thêm khách sạn') . ' · Homi')
@section('banner_tag', 'Admin · Hotels')
@section('banner_title', $isEdit ? 'Sửa khách sạn' : 'Thêm khách sạn mới')
@section('banner_subtitle', 'Điền đầy đủ thông tin bắt buộc (tên, thành phố, địa chỉ) rồi lưu lại.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">{{ $isEdit ? 'Cập nhật' : 'Tạo mới' }}</div>
            <h2 class="section-title" style="margin-bottom: 6px;">{{ $isEdit ? $hotel->name : 'Khách sạn mới' }}</h2>
            <p class="section-desc">Các trường có dấu * là bắt buộc.</p>
        </div>

        <a href="{{ route('admin.hotels.index') }}" class="btn btn-outline">Quay lại danh sách</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST"
        action="{{ $isEdit ? route('admin.hotels.update', $hotel->id) : route('admin.hotels.store') }}"
        class="form-grid">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="name">Tên khách sạn *</label>
            <input id="name" type="text" name="name" value="{{ old('name', $hotel->name ?? '') }}"
                placeholder="VD: Homi Đà Nẵng Hotel" required>
        </div>

        <div class="form-group">
            <label for="city">Thành phố *</label>
            <input id="city" type="text" name="city" value="{{ old('city', $hotel->city ?? '') }}"
                placeholder="VD: Đà Nẵng" required>
        </div>

        <div class="form-group">
            <label for="district">Quận/huyện</label>
            <input id="district" type="text" name="district" value="{{ old('district', $hotel->district ?? '') }}"
                placeholder="VD: Hải Châu">
        </div>

        <div class="form-group">
            <label for="address">Địa chỉ *</label>
            <input id="address" type="text" name="address" value="{{ old('address', $hotel->address ?? '') }}"
                placeholder="VD: 123 Bạch Đằng, Hải Châu, Đà Nẵng" required>
        </div>

        <div class="form-group">
            <label for="star_rating">Xếp hạng sao (1-5)</label>
            <select id="star_rating" name="star_rating">
                <option value="">Không chọn</option>
                @for ($i = 1; $i <= 5; $i++)
                    <option value="{{ $i }}" @selected((int) old('star_rating', $hotel->star_rating ?? 0) === $i)>{{ $i }} sao</option>
                @endfor
            </select>
        </div>

        <div class="form-group">
            <label for="description">Mô tả</label>
            <textarea id="description" name="description" rows="4"
                placeholder="Mô tả ngắn về khách sạn...">{{ old('description', $hotel->description ?? '') }}</textarea>
        </div>

        <div class="form-group">
            <label>Tiện ích</label>
            <div class="checkbox-grid">
                @forelse ($amenities as $amenity)
                    <label class="checkbox-item">
                        <input type="checkbox" name="amenity_ids[]" value="{{ $amenity->id }}"
                            @checked(in_array($amenity->id, old('amenity_ids', $selectedAmenityIds)))>
                        {{ $amenity->name }}
                    </label>
                @empty
                    <span class="section-desc">Chưa có tiện ích nào trong hệ thống.</span>
                @endforelse
            </div>
        </div>

        <div class="form-group">
            <label for="images_text">Ảnh khách sạn (mỗi dòng 1 đường dẫn/URL)</label>
            <textarea id="images_text" name="images_text" rows="3"
                placeholder="hotels/anh1.jpg&#10;hotels/anh2.jpg">{{ old('images_text', $isEdit ? $hotel->images->pluck('path')->implode("\n") : '') }}</textarea>
            @if ($isEdit)
                <p class="section-desc">Lưu ý: nếu nhập ảnh mới ở đây, toàn bộ ảnh cũ sẽ bị thay thế. Để trống nếu không muốn đổi ảnh.</p>
            @endif
        </div>

        <button type="submit" class="btn btn-primary btn-block">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo khách sạn' }}</button>
    </form>
</div>
@endsection
