@extends('layouts.app')

@section('title', 'Thông tin khách sạn · Homi')
@section('banner_tag', 'Admin · Hotel Info')
@section('banner_title', 'Thông tin khách sạn')
@section('banner_subtitle', 'Cập nhật thông tin duy nhất của khách sạn Homi.')

@section('content')
<div class="card">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.hotel-info.update') }}" class="form-grid">
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

        <div class="form-group">
            <label for="hotline">Hotline</label>
            <input id="hotline" type="text" name="hotline" value="{{ old('hotline', $hotel->hotline) }}">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $hotel->email) }}">
        </div>

        <div class="form-group">
            <label for="check_in_time">Giờ nhận phòng</label>
            <input id="check_in_time" type="text" name="check_in_time" value="{{ old('check_in_time', $hotel->check_in_time) }}" placeholder="VD: 14:00">
        </div>

        <div class="form-group">
            <label for="check_out_time">Giờ trả phòng</label>
            <input id="check_out_time" type="text" name="check_out_time" value="{{ old('check_out_time', $hotel->check_out_time) }}" placeholder="VD: 12:00">
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
            <textarea id="description" name="description" rows="4">{{ old('description', $hotel->description) }}</textarea>
        </div>

        <div class="form-group">
            <label for="policies">Chính sách</label>
            <textarea id="policies" name="policies" rows="4" placeholder="Chính sách hủy phòng, trẻ em, thú cưng...">{{ old('policies', $hotel->policies) }}</textarea>
        </div>

        <div class="form-group">
            <label class="checkbox-item">
                <input type="checkbox" name="is_open" value="1" @checked(old('is_open', $hotel->is_open))>
                Khách sạn đang mở nhận đặt phòng
            </label>
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
                placeholder="hotel/anh1.jpg&#10;hotel/anh2.jpg">{{ old('images_text', $hotel->images->pluck('path')->implode("\n")) }}</textarea>
            <p class="section-desc">Lưu ý: nếu nhập ảnh mới ở đây, toàn bộ ảnh cũ sẽ bị thay thế. Để trống nếu không muốn đổi ảnh.</p>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Lưu thay đổi</button>
    </form>
</div>
@endsection
