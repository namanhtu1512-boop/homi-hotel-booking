@extends('layouts.app')

@section('title', $hotel->name . ' · Homi')
@section('banner_tag', 'Homi Hotel Booking')
@section('banner_title', $hotel->name)
@section('banner_subtitle', $hotel->description ?: 'Hệ thống quản lý khách sạn Homi.')

@section('content')
@if ($hotel->images->isNotEmpty())
    @php
        $heroImageUrls = $hotel->images->map(
            fn ($image) => \Illuminate\Support\Str::startsWith($image->path, ['http://', 'https://'])
                ? $image->path
                : asset('storage/' . $image->path)
        );
    @endphp
    <div class="card">
        <div class="section-kicker">Hình ảnh</div>
        <h2 class="section-title" style="margin-bottom: 14px;">Không gian tại {{ $hotel->name }}</h2>

        <div class="hotel-gallery-main" style="background-image: url('{{ $heroImageUrls->first() }}');"></div>

        @if ($heroImageUrls->count() > 1)
            <div class="hotel-gallery-thumbs">
                @foreach ($heroImageUrls->skip(1) as $url)
                    <div class="hotel-gallery-thumb" style="background-image: url('{{ $url }}');"></div>
                @endforeach
            </div>
        @endif
    </div>
@endif

<div class="card">
    <div class="section-kicker">Giới thiệu</div>
    <h2 class="section-title" style="margin-bottom: 6px;">{{ $hotel->name }}</h2>
    <p class="section-desc">{{ $hotel->address }}</p>

    <div class="info-list">
        @if ($hotel->star_rating)
            <div class="info-item">
                <span class="label">Xếp hạng</span>
                <span class="value">{{ $hotel->star_rating }} sao</span>
            </div>
        @endif

        @if ($hotel->hotline)
            <div class="info-item">
                <span class="label">Hotline</span>
                <span class="value">{{ $hotel->hotline }}</span>
            </div>
        @endif

        @if ($hotel->check_in_time || $hotel->check_out_time)
            <div class="info-item">
                <span class="label">Giờ nhận / trả phòng</span>
                <span class="value">{{ $hotel->check_in_time ?? '--' }} / {{ $hotel->check_out_time ?? '--' }}</span>
            </div>
        @endif
    </div>

    @if ($hotel->amenities->isNotEmpty())
        <div class="section-kicker" style="margin-top: 22px;">Tiện ích</div>
        <div class="checkbox-grid" style="margin-top: 10px;">
            @foreach ($hotel->amenities as $amenity)
                <span class="badge badge-blue">{{ $amenity->name }}</span>
            @endforeach
        </div>
    @endif
</div>

@if ($featuredRoomTypes->isNotEmpty())
    <div class="card">
        <div class="page-actions">
            <div>
                <div class="section-kicker">Phòng nổi bật</div>
                <h2 class="section-title" style="margin-bottom: 6px;">
                    Giá chỉ từ {{ number_format($featuredRoomTypes->min('price_per_night'), 0, ',', '.') }}đ / đêm
                </h2>
                <p class="section-desc">Chọn nhanh một trong những loại phòng được đặt nhiều nhất.</p>
            </div>

            <a href="{{ route('rooms.index') }}" class="btn btn-primary">Xem tất cả phòng</a>
        </div>

        <div class="room-grid">
            @foreach ($featuredRoomTypes as $roomType)
                @php
                    $cover = $roomType->images->first();
                    $coverUrl = $cover ? (\Illuminate\Support\Str::startsWith($cover->path, ['http://', 'https://']) ? $cover->path : asset('storage/' . $cover->path)) : null;
                @endphp
                <div class="room-card">
                    <div class="room-card-image" @if ($coverUrl) style="background-image: url('{{ $coverUrl }}');" @endif>
                        @unless ($coverUrl)
                            {{ $roomType->name }}
                        @endunless
                    </div>

                    <div class="room-card-body">
                        <h3 class="room-card-title">{{ $roomType->name }}</h3>

                        <div class="room-card-meta">
                            <span class="badge badge-blue">{{ $roomType->capacity }} khách</span>
                            <span class="badge badge-green">Còn {{ $roomType->total_rooms }} phòng</span>
                        </div>

                        <div class="room-card-footer">
                            <span class="room-card-price">{{ number_format($roomType->price_per_night, 0, ',', '.') }}đ / đêm</span>
                            <a href="{{ route('rooms.show', $roomType->id) }}" class="btn btn-outline btn-sm">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="card">
    <div class="section-kicker">Sẵn sàng nghỉ dưỡng?</div>
    <h2 class="section-title" style="margin-bottom: 10px;">Đặt phòng tại {{ $hotel->name }} chỉ trong vài bước</h2>
    <p class="section-desc" style="margin-bottom: 18px;">
        Xem phòng trống theo ngày bạn cần, chọn loại phòng phù hợp và nhận xác nhận đặt phòng ngay lập tức.
    </p>

    <div class="action-row">
        <a href="{{ route('rooms.index') }}" class="btn btn-primary">Tìm phòng ngay</a>

        @guest
            <a href="{{ route('register') }}" class="btn btn-outline">Đăng ký tài khoản</a>
        @endguest
    </div>
</div>
@endsection
