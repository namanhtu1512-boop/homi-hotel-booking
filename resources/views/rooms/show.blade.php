@extends('layouts.app')

@section('title', $roomType->name . ' · Homi')
@section('banner_tag', 'Chi tiết phòng')
@section('banner_title', $roomType->name)
@section('banner_subtitle', 'Sức chứa ' . $roomType->capacity . ' khách · ' . number_format($roomType->price_per_night, 0, ',', '.') . 'đ / đêm')

@section('content')
<div class="dashboard-grid">
    <div>
        <div class="card">
            @if ($roomType->images->isNotEmpty())
                @php
                    $roomImageUrls = $roomType->images->map(
                        fn ($image) => \Illuminate\Support\Str::startsWith($image->path, ['http://', 'https://'])
                            ? $image->path
                            : asset('storage/' . $image->path)
                    );
                @endphp

                <div class="hotel-gallery-main" style="background-image: url('{{ $roomImageUrls->first() }}');"></div>

                @if ($roomImageUrls->count() > 1)
                    <div class="hotel-gallery-thumbs">
                        @foreach ($roomImageUrls->skip(1) as $url)
                            <div class="hotel-gallery-thumb" style="background-image: url('{{ $url }}');"></div>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="hotel-gallery-main">Chưa có ảnh</div>
            @endif

            <div class="section-kicker" style="margin-top: 22px;">Mô tả</div>
            <h2 class="section-title" style="margin-bottom: 6px;">{{ $roomType->name }}</h2>
            <p class="section-desc">{{ $roomType->description ?: 'Chưa có mô tả chi tiết.' }}</p>

            <div class="room-card-meta" style="margin-top: 16px;">
                <span class="badge badge-blue">{{ $roomType->capacity }} khách</span>
                @if ($roomType->bed_type)
                    <span class="badge badge-blue">{{ $roomType->bed_type }}</span>
                @endif
                @if ($roomType->area)
                    <span class="badge badge-blue">{{ $roomType->area }} m²</span>
                @endif
                <span class="badge badge-green">Tổng {{ $roomType->total_rooms }} phòng</span>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="section-kicker">Kiểm tra phòng trống</div>
            <h2 class="section-title" style="margin-bottom: 14px;">{{ number_format($roomType->price_per_night, 0, ',', '.') }}đ / đêm</h2>

            <form method="GET" action="{{ route('rooms.show', $roomType->id) }}" class="form-grid">
                <div class="form-group">
                    <label for="check_in">Ngày nhận phòng</label>
                    <input type="date" id="check_in" name="check_in" value="{{ $checkIn }}" required>
                </div>

                <div class="form-group">
                    <label for="check_out">Ngày trả phòng</label>
                    <input type="date" id="check_out" name="check_out" value="{{ $checkOut }}" required>
                </div>

                <div class="form-group">
                    <label for="quantity">Số phòng</label>
                    <input type="number" id="quantity" name="quantity" min="1" max="10" value="{{ $quantity }}">
                </div>

                <button type="submit" class="btn btn-outline btn-block">Kiểm tra phòng trống</button>
            </form>

            @if ($availabilityError)
                <div class="alert alert-danger" style="margin-top: 16px;">{{ $availabilityError }}</div>
            @elseif ($availability)
                <div class="alert {{ $availability['can_book'] ? 'alert-success' : 'alert-danger' }}" style="margin-top: 16px;">
                    @if ($availability['can_book'])
                        Còn {{ $availability['available_quantity'] }} phòng trống cho {{ $availability['nights'] }} đêm bạn chọn.
                    @else
                        Chỉ còn {{ $availability['available_quantity'] }} phòng trống, không đủ cho {{ $quantity }} phòng bạn yêu cầu.
                    @endif
                </div>

                @if ($availability['can_book'])
                    <a href="{{ route('customer.booking.create', [
                        'room_type_id' => $roomType->id,
                        'check_in'     => $checkIn,
                        'check_out'    => $checkOut,
                        'quantity'     => $quantity,
                    ]) }}" class="btn btn-primary btn-block" style="margin-top: 10px;">Đặt phòng ngay</a>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
