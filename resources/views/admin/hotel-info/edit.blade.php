@extends('layouts.admin')

@section('title', 'Sửa thông tin khách sạn · Homi Admin')
@section('page_title', 'Sửa thông tin khách sạn')
@section('page_subtitle', 'Các trường có dấu * là bắt buộc.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.hotel-info.show') }}" class="btn btn-outline">Quay lại</a>
    </div>

    <form method="POST" action="{{ route('admin.hotel-info.update') }}" class="form-grid" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">Tên khách sạn *</label>
            <input id="name" type="text" name="name" value="{{ old('name', $hotel->name) }}" required>
        </div>

        <div class="form-group">
            <label for="address">Địa chỉ *</label>
            <input id="address" type="text" name="address" value="{{ old('address', $hotel->address) }}" required>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone', $hotel->phone) }}">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $hotel->email) }}">
            </div>
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

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="check_in_time">Giờ nhận phòng (HH:mm)</label>
                <input id="check_in_time" type="time" name="check_in_time" value="{{ old('check_in_time', $hotel->check_in_time) }}">
            </div>
            <div class="form-group">
                <label for="check_out_time">Giờ trả phòng (HH:mm)</label>
                <input id="check_out_time" type="time" name="check_out_time" value="{{ old('check_out_time', $hotel->check_out_time) }}">
            </div>
        </div>

        <div class="form-group">
            <label for="description">Mô tả</label>
            <textarea id="description" name="description" rows="4">{{ old('description', $hotel->description) }}</textarea>
        </div>

        <div class="form-group">
            <label for="policies">Chính sách</label>
            <textarea id="policies" name="policies" rows="4" placeholder="Mỗi dòng 1 quy định">{{ old('policies', $hotel->policies) }}</textarea>
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
            <label for="image_files">Tải ảnh lên từ máy</label>
            <input id="image_files" type="file" name="image_files[]" multiple accept="image/*">
            @error('image_files.*')
                <p style="color: red; font-size: 13px; margin-top: 4px;">{{ $message }}</p>
            @enderror

            @if ($hotel->images->isNotEmpty())
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;">
                    @foreach ($hotel->images as $image)
                        <img
                            src="{{ Str::startsWith($image->path, ['http://', 'https://']) ? $image->path : asset('storage/' . $image->path) }}"
                            style="width: 100px; height: 70px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;"
                        >
                    @endforeach
                </div>
                <p class="section-desc" style="margin-top: 6px;">Ảnh hiện tại — tải ảnh mới lên sẽ thay thế toàn bộ ảnh trên.</p>
            @endif
        </div>

        <div class="form-group">
            <label for="images_text">Hoặc nhập đường dẫn / URL (mỗi dòng 1 ảnh)</label>
            <textarea id="images_text" name="images_text" rows="3"
                placeholder="hotel/anh1.jpg&#10;https://example.com/anh2.jpg">{{ old('images_text', $hotel->images->pluck('path')->implode("\n")) }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Lưu thay đổi</button>
    </form>
</div>
@endsection
