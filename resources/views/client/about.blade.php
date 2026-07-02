@extends('layouts.app')

@section('title', ($hotel->name ?? 'Homi') . ' · Giới thiệu')
@section('banner_tag', 'Về chúng tôi')
@section('banner_title', $hotel->name ?? 'Homi Hotel')
@section('banner_subtitle', $hotel->description ?? 'Khám phá khách sạn và tiện ích của chúng tôi.')

@section('content')

{{-- Thông tin cơ bản --}}
<div class="card">
    <div class="section-kicker">Thông tin khách sạn</div>
    <h2 class="section-title">{{ $hotel->name }}</h2>

    @if ($hotel->description)
        <p class="section-desc">{{ $hotel->description }}</p>
    @endif

    <div class="info-list" style="margin-top: 20px;">
        @if ($hotel->address)
            <div class="info-item">
                <span class="label">Địa chỉ</span>
                <span class="value">{{ $hotel->address }}</span>
            </div>
        @endif

        @if ($hotel->phone)
            <div class="info-item">
                <span class="label">Điện thoại</span>
                <span class="value">{{ $hotel->phone }}</span>
            </div>
        @endif

        @if ($hotel->email)
            <div class="info-item">
                <span class="label">Email</span>
                <span class="value">{{ $hotel->email }}</span>
            </div>
        @endif

        @if ($hotel->check_in_time)
            <div class="info-item">
                <span class="label">Giờ nhận phòng</span>
                <span class="value">{{ $hotel->check_in_time }}</span>
            </div>
        @endif

        @if ($hotel->check_out_time)
            <div class="info-item">
                <span class="label">Giờ trả phòng</span>
                <span class="value">{{ $hotel->check_out_time }}</span>
            </div>
        @endif

        @if ($hotel->star_rating)
            <div class="info-item">
                <span class="label">Hạng sao</span>
                <span class="value">{{ $hotel->star_rating }} ★</span>
            </div>
        @endif
    </div>
</div>

{{-- Ảnh khách sạn --}}
@if ($hotel->images && $hotel->images->isNotEmpty())
    <div class="card" style="margin-top: 22px;">
        <div class="section-kicker">Hình ảnh</div>
        <h3 class="section-title" style="font-size: 20px;">Khám phá không gian</h3>

        <div class="hotel-gallery-main"
             style="background-image: url('{{ $hotel->images->first()->image_url }}');">
            @if ($hotel->images->first()->image_url === null)
                <span>Ảnh khách sạn</span>
            @endif
        </div>

        @if ($hotel->images->count() > 1)
            <div class="hotel-gallery-thumbs" style="margin-top: 12px;">
                @foreach ($hotel->images->skip(1) as $img)
                    <div class="hotel-gallery-thumb"
                         style="background-image: url('{{ $img->image_url }}');"></div>
                @endforeach
            </div>
        @endif
    </div>
@endif

{{-- Tiện ích --}}
@if ($hotel->amenities && $hotel->amenities->isNotEmpty())
    <div class="card" style="margin-top: 22px;">
        <div class="section-kicker">Tiện ích</div>
        <h3 class="section-title" style="font-size: 20px;">Tiện ích khách sạn</h3>

        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px;">
            @foreach ($hotel->amenities as $amenity)
                <span class="badge badge-blue" style="padding: 8px 14px; font-size: 13px;">
                    @if ($amenity->icon) {{ $amenity->icon }} @endif
                    {{ $amenity->name }}
                </span>
            @endforeach
        </div>
    </div>
@endif

{{-- Chính sách --}}
@if ($hotel->policies)
    <div class="card" style="margin-top: 22px;">
        <div class="section-kicker">Chính sách</div>
        <h3 class="section-title" style="font-size: 20px;">Chính sách khách sạn</h3>
        <p class="section-desc" style="margin-top: 12px; white-space: pre-line;">{{ $hotel->policies }}</p>
    </div>
@endif

{{-- CTA --}}
<div class="card" style="margin-top: 22px; text-align: center;">
    <h3 class="section-title" style="font-size: 22px; margin-bottom: 8px;">Sẵn sàng đặt phòng?</h3>
    <p class="section-desc" style="margin-bottom: 20px;">Xem các loại phòng và kiểm tra phòng trống ngay hôm nay.</p>
    <a href="{{ route('rooms.index') }}" class="btn btn-primary" style="display: inline-flex; min-width: 200px;">Xem phòng ngay</a>
</div>

@endsection
