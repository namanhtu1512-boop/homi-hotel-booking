@extends('layouts.admin')

@section('title', 'Thông tin khách sạn · Homi Admin')
@section('page_title', 'Thông tin khách sạn')
@section('page_subtitle', 'Hệ thống Homi chỉ vận hành 1 khách sạn duy nhất.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">{{ $hotel->status === 'active' ? 'Đang hoạt động' : 'Đang bảo trì' }}</div>
            <h2 class="section-title">{{ $hotel->name }}</h2>
            <p class="section-desc">{{ $hotel->address }}</p>
        </div>

        <div class="action-row">
            <a href="{{ route('admin.hotel-info.edit') }}" class="btn btn-primary">Sửa thông tin</a>
            <form method="POST" action="{{ route('admin.hotel-info.toggle-maintenance') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline">
                    {{ $hotel->status === 'active' ? 'Chuyển sang bảo trì' : 'Mở hoạt động trở lại' }}
                </button>
            </form>
        </div>
    </div>

    @if ($hotel->images->isNotEmpty())
    <div style="margin-bottom: 24px;">
        <div class="section-kicker" style="margin-bottom: 12px;">Hình ảnh giới thiệu</div>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;">
            @foreach ($hotel->images as $image)
                <div style="border-radius: 8px; overflow: hidden; aspect-ratio: 16/9; background: #f0f0f0;">
                    <img
                        src="{{ Str::startsWith($image->path, ['http://', 'https://']) ? $image->path : asset('storage/' . $image->path) }}"
                        alt="{{ $hotel->name }}"
                        style="width: 100%; height: 100%; object-fit: cover; display: block;"
                        loading="lazy"
                    >
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="form-grid" style="grid-template-columns: 1fr 1fr; display: grid;">
        <div>
            <div class="section-kicker" style="margin-top: 12px;">Mô tả</div>
            <p class="section-desc">{{ $hotel->description ?: 'Chưa có mô tả.' }}</p>

            <div class="section-kicker" style="margin-top: 16px;">Chính sách</div>
            <p class="section-desc" style="white-space: pre-line;">{{ $hotel->policies ?: 'Chưa có chính sách.' }}</p>
        </div>

        <div>
            <div class="section-kicker" style="margin-top: 12px;">Giờ nhận / trả phòng</div>
            <p class="section-desc">
                Nhận phòng: {{ $hotel->check_in_time ?: 'Chưa cập nhật' }}<br>
                Trả phòng: {{ $hotel->check_out_time ?: 'Chưa cập nhật' }}
            </p>

            <div class="section-kicker" style="margin-top: 16px;">Xếp hạng sao</div>
            <p class="section-desc">{{ $hotel->star_rating ? $hotel->star_rating . ' sao' : 'Chưa xếp hạng' }}</p>

            <div class="section-kicker" style="margin-top: 16px;">Tiện ích</div>
            @if ($hotel->amenities->isEmpty())
                <p class="section-desc">Chưa gán tiện ích nào.</p>
            @else
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    @foreach ($hotel->amenities as $amenity)
                        <span class="badge badge-blue">{{ $amenity->name }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
