@extends('layouts.app')

@section('title', 'Danh sách phòng · Homi')
@section('banner_tag', 'Phòng')
@section('banner_title', 'Tìm phòng phù hợp với bạn')
@section('banner_subtitle', 'Xem và lọc các loại phòng đang nhận đặt tại Homi.')

@section('content')
<div class="card">
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('rooms.index') }}" class="filter-bar">
        <input type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Tìm theo tên phòng...">
        <input type="number" name="min_price" value="{{ $filters['min_price'] ?? '' }}" placeholder="Giá từ" min="0">
        <input type="number" name="max_price" value="{{ $filters['max_price'] ?? '' }}" placeholder="Giá đến" min="0">
        <input type="number" name="capacity" value="{{ $filters['capacity'] ?? '' }}" placeholder="Sức chứa tối thiểu" min="1">
        <input type="date" name="check_in" value="{{ $filters['check_in'] ?? '' }}">
        <input type="date" name="check_out" value="{{ $filters['check_out'] ?? '' }}">
        <button type="submit" class="btn btn-primary">Lọc phòng</button>
        <a href="{{ route('rooms.index') }}" class="btn btn-outline">Xóa lọc</a>
    </form>
</div>

<div class="card">
    <div class="section-kicker">Kết quả</div>
    <h2 class="section-title" style="margin-bottom: 18px;">{{ $roomTypes->count() }} loại phòng</h2>

    @if ($roomTypes->isEmpty())
        <div class="empty-box">Không tìm thấy loại phòng phù hợp với bộ lọc.</div>
    @else
        <div class="room-grid">
            @foreach ($roomTypes as $roomType)
                @php
                    $cover = $roomType->images->first();
                    $coverUrl = $cover ? (\Illuminate\Support\Str::startsWith($cover->path, ['http://', 'https://']) ? $cover->path : asset('storage/' . $cover->path)) : null;
                @endphp
                <div class="room-card">
                    <div class="room-card-image" @if ($coverUrl) style="background-image: url('{{ $coverUrl }}');" @endif>
                        @unless ($coverUrl)
                            Chưa có ảnh
                        @endunless
                    </div>

                    <div class="room-card-body">
                        <h3 class="room-card-title">{{ $roomType->name }}</h3>

                        @if ($roomType->description)
                            <p class="room-card-desc">{{ \Illuminate\Support\Str::limit($roomType->description, 110) }}</p>
                        @endif

                        <div class="room-card-meta">
                            <span class="badge badge-blue">{{ $roomType->capacity }} khách</span>
                            @if ($roomType->bed_type)
                                <span class="badge badge-blue">{{ $roomType->bed_type }}</span>
                            @endif
                            @if ($roomType->area)
                                <span class="badge badge-blue">{{ $roomType->area }} m²</span>
                            @endif
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
    @endif
</div>
@endsection
