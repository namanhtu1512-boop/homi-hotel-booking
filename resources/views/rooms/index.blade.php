@extends('layouts.app')

@section('title', 'Danh sách phòng · Homi')
@section('meta_description', 'Xem và lọc danh sách loại phòng tại ' . $hotel->name . ' theo giá, sức chứa và tiện ích — kiểm tra phòng trống theo ngày.')
@section('banner_tag', 'Phòng')
@section('banner_title', 'Tìm phòng phù hợp với bạn')
@section('banner_subtitle', 'Xem và lọc các loại phòng đang nhận đặt tại ' . $hotel->name . '.')

@section('content')
@php $hasFilters = ! empty(array_filter($filters)); @endphp

<div class="grid gap-6 md:grid-cols-[280px_1fr]">
    <aside class="card h-fit space-y-5">
        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('rooms.index') }}" class="space-y-5">
            <div>
                <div class="mb-2 text-sm font-bold text-slate-900 dark:text-white">Xếp hạng khách sạn</div>
                <div class="text-accent">{{ str_repeat('★', $hotel->star_rating ?? 0) }}{{ str_repeat('☆', 5 - ($hotel->star_rating ?? 0)) }}</div>
            </div>

            <div>
                <label class="form-label" for="keyword">Từ khoá</label>
                <input id="keyword" type="text" name="keyword" class="input" value="{{ $filters['keyword'] ?? '' }}" placeholder="Tên phòng...">
            </div>

            <div>
                <div class="form-label">Khoảng giá (đ/đêm)</div>
                <div class="flex items-center gap-2">
                    <input type="number" name="min_price" class="input" value="{{ $filters['min_price'] ?? '' }}" placeholder="Từ" min="0">
                    <span class="text-slate-400">—</span>
                    <input type="number" name="max_price" class="input" value="{{ $filters['max_price'] ?? '' }}" placeholder="Đến" min="0">
                </div>
            </div>

            <div>
                <label class="form-label" for="capacity">Sức chứa tối thiểu</label>
                <select id="capacity" name="capacity" class="input">
                    <option value="">Bất kỳ</option>
                    @foreach ([1,2,3,4,5,6] as $n)
                        <option value="{{ $n }}" @selected(($filters['capacity'] ?? '') == $n)>{{ $n }} khách</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="bed_type">Loại phòng</label>
                <input id="bed_type" type="text" name="bed_type" class="input" value="{{ $filters['bed_type'] ?? '' }}" placeholder="VD: Giường đôi">
            </div>

            <div>
                <div class="form-label">Thời gian lưu trú</div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" name="check_in" class="input" value="{{ $filters['check_in'] ?? '' }}" min="{{ now()->toDateString() }}">
                    <input type="date" name="check_out" class="input" value="{{ $filters['check_out'] ?? '' }}" min="{{ now()->addDay()->toDateString() }}">
                </div>
            </div>

            <div>
                <label class="form-label" for="quantity">Số phòng</label>
                <select id="quantity" name="quantity" class="input">
                    @foreach ([1,2,3,4] as $n)
                        <option value="{{ $n }}" @selected(($filters['quantity'] ?? 1) == $n)>{{ $n }} phòng</option>
                    @endforeach
                </select>
            </div>

            <input type="hidden" name="sort" value="{{ $filters['sort'] ?? '' }}">

            <div class="flex gap-2">
                <button type="submit" class="btn-primary w-full">Lọc phòng</button>
                @if ($hasFilters)
                    <a href="{{ route('rooms.index') }}" class="btn-outline w-full text-center">Xóa lọc</a>
                @endif
            </div>
        </form>
    </aside>

    <div>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <span class="section-kicker">Kết quả</span>
                <h2 class="section-title">{{ $roomTypes->total() ?? $roomTypes->count() }} loại phòng</h2>
            </div>

            <form method="GET" action="{{ route('rooms.index') }}" class="flex items-center gap-2">
                @foreach ($filters as $key => $value)
                    @if ($key !== 'sort' && $value !== null && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label class="text-sm font-semibold text-slate-500 dark:text-slate-400" for="sort">Sắp xếp</label>
                <select id="sort" name="sort" class="input" onchange="this.form.submit()">
                    <option value="price_asc" @selected(($filters['sort'] ?? 'price_asc') === 'price_asc')>Giá thấp đến cao</option>
                    <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Giá cao đến thấp</option>
                    <option value="rating" @selected(($filters['sort'] ?? '') === 'rating')>Đánh giá</option>
                    <option value="newest" @selected(($filters['sort'] ?? '') === 'newest')>Mới nhất</option>
                </select>
            </form>
        </div>

        @if ($roomTypes->isEmpty())
            <div class="empty-box">Không tìm thấy loại phòng phù hợp với bộ lọc.</div>
        @else
            <div class="room-grid">
                @foreach ($roomTypes as $roomType)
                    @php
                        $cover = $roomType->images->first();
                        $rating = $ratings[$roomType->id] ?? null;
                    @endphp
                    <div class="room-card">
                        <a href="{{ route('rooms.show', $roomType->id) }}" class="room-card-image" @if ($cover) style="background-image: url('{{ $cover->image_url }}');" @endif>
                            @include('partials._seasonal-ribbon', ['room' => $roomType, 'seasonalRates' => $seasonalRates])
                            @unless ($cover)
                                Chưa có ảnh
                            @endunless
                        </a>

                        <div class="room-card-body">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="room-card-title">{{ $roomType->name }}</h3>
                                @if ($rating && $rating['count'] > 0)
                                    <span class="badge badge-orange shrink-0">★ {{ $rating['avg'] }} ({{ $rating['count'] }})</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-400">{{ $hotel->address }}</p>

                            @if ($roomType->description)
                                <p class="room-card-desc">{{ Str::limit($roomType->description, 110) }}</p>
                            @endif

                            <div class="room-card-meta">
                                <span class="badge badge-blue">{{ $roomType->capacity }} khách</span>
                                @if ($roomType->bed_type)
                                    <span class="badge badge-blue">{{ $roomType->bed_type }}</span>
                                @endif
                                @if ($roomType->area)
                                    <span class="badge badge-blue">{{ $roomType->area }} m²</span>
                                @endif
                                <span class="badge badge-green">Còn {{ $roomType->available_quantity }} phòng</span>
                            </div>

                            <div class="room-card-footer">
                                @include('partials._room-price', ['room' => $roomType, 'seasonalRates' => $seasonalRates])
                                <div class="action-row">
                                    @auth
                                        @if (auth()->user()->role === 'customer')
                                            <form method="POST" action="{{ route('customer.wishlist.store', $roomType->id) }}">
                                                @csrf
                                                <button type="submit" class="btn-outline btn-sm" title="Thêm vào danh sách chờ">☆ Yêu thích</button>
                                            </form>
                                        @endif
                                    @endauth
                                    <a href="{{ route('rooms.show', $roomType->id) }}" class="btn-outline btn-sm">Xem chi tiết</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($roomTypes->hasPages())
                <div class="mt-8 flex justify-center">{{ $roomTypes->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
