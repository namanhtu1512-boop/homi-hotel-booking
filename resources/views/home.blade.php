@extends('layouts.app')

@section('title', $hotel->name . ' · Homi')
@section('banner_tag', 'Homi Hotel Booking')
@section('banner_title', $hotel->name)
@section('banner_subtitle', $hotel->description ?: 'Hệ thống quản lý khách sạn Homi.')

@section('content')
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
@endsection
