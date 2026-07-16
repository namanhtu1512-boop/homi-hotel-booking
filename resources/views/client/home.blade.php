@extends('layouts.app')

@php
    $heroImage = optional($banners->first())->image_url ?? optional($hotel->images->first())->image_url ?? null;
    $hasFilters = ! empty(array_filter($filters));
@endphp

@section('title', $hotel->name . ' · Homi')
@section('banner_tag', $hotel->star_rating ? $hotel->star_rating . ' sao · Homi Hotel Booking' : 'Homi Hotel Booking')
@section('banner_title', $hotel->name)
@section('banner_subtitle', $hotel->description ?: 'Khám phá các loại phòng, kiểm tra thời gian lưu trú và đặt phòng trực tuyến ngay trên Homi.')

@if ($heroImage)
    @section('hero_bg_image', $heroImage)
@endif

@section('hero_extra')
    <form method="GET" action="{{ route('home') }}" class="grid gap-3 rounded-2xl bg-white p-4 shadow-xl sm:grid-cols-2 lg:grid-cols-6 dark:bg-slate-900">
        <div class="lg:col-span-2">
            <label class="form-label !text-slate-500">Địa điểm</label>
            <div class="input flex items-center bg-slate-100 text-slate-500 dark:bg-slate-800">{{ $hotel->address }}</div>
        </div>
        <div>
            <label class="form-label !text-slate-500" for="check_in">Nhận phòng</label>
            <input id="check_in" type="date" name="check_in" class="input" value="{{ $filters['check_in'] ?? '' }}" min="{{ now()->toDateString() }}">
        </div>
        <div>
            <label class="form-label !text-slate-500" for="check_out">Trả phòng</label>
            <input id="check_out" type="date" name="check_out" class="input" value="{{ $filters['check_out'] ?? '' }}" min="{{ now()->addDay()->toDateString() }}">
        </div>
        <div>
            <label class="form-label !text-slate-500" for="quantity">Số phòng</label>
            <select id="quantity" name="quantity" class="input">
                @foreach ([1,2,3,4] as $n)
                    <option value="{{ $n }}" @selected(($filters['quantity'] ?? 1) == $n)>{{ $n }} phòng</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label !text-slate-500" for="capacity">Số khách</label>
            <select id="capacity" name="capacity" class="input">
                <option value="">Bất kỳ</option>
                @foreach ([1,2,3,4] as $n)
                    <option value="{{ $n }}" @selected(($filters['capacity'] ?? '') == $n)>{{ $n }} khách</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
            <input type="text" name="keyword" maxlength="100" placeholder="Từ khoá: Standard, Deluxe..." class="input" value="{{ $filters['keyword'] ?? '' }}">
            <button type="submit" class="btn-primary shrink-0">Tìm phòng</button>
            @if ($hasFilters)
                <a href="{{ route('home') }}" class="btn-outline shrink-0">Xoá lọc</a>
            @endif
        </div>
    </form>
@endsection

@section('content')
    @if ($banners->isNotEmpty())
        <section>
            <div class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2">
                @foreach ($banners as $banner)
                    <a href="{{ $banner->link_url ?: '#' }}"
                        class="group relative block aspect-[16/7] w-full shrink-0 snap-start overflow-hidden rounded-2xl bg-slate-100 sm:w-[min(100%,960px)] dark:bg-slate-800">
                        <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-4 text-white sm:p-6">
                            <h3 class="text-lg font-bold sm:text-2xl">{{ $banner->title }}</h3>
                            @if ($banner->subtitle)
                                <p class="mt-1 max-w-lg text-sm text-white/90 sm:text-base">{{ $banner->subtitle }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section>
        <span class="section-kicker">Giới thiệu</span>
        <h2 class="section-title">Vì sao chọn {{ $hotel->name }}</h2>
        <p class="section-desc mt-1">{{ $hotel->address }}</p>

        <div class="mt-4">
            @include('partials._map-embed', ['hotel' => $hotel])
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            @forelse ($hotel->amenities as $amenity)
                <span class="badge badge-blue">{{ $amenity->name }}</span>
            @empty
                <span class="badge badge-blue">Đang cập nhật tiện ích</span>
            @endforelse
        </div>

        @if ($hotel->images->isNotEmpty())
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($hotel->images->take(4) as $image)
                    <div class="aspect-video overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-800">
                        <img src="{{ $image->image_url }}" alt="{{ $hotel->name }}" class="h-full w-full object-cover" loading="lazy">
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if ($featuredRooms->isNotEmpty())
        <section>
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <span class="section-kicker">Gợi ý cho bạn</span>
                    <h2 class="section-title">Phòng nổi bật</h2>
                </div>
                <a href="{{ route('rooms.index') }}" class="btn-outline">Xem tất cả phòng</a>
            </div>

            <div class="room-grid">
                @foreach ($featuredRooms as $room)
                    <article class="room-card">
                        <a href="{{ route('rooms.show', $room->id) }}" class="room-card-image" @if ($room->images->first()) style="background-image:url('{{ $room->images->first()->image_url }}')" @endif>
                            @if (! $room->images->first())
                                {{ $room->name }}
                            @endif
                        </a>
                        <div class="room-card-body">
                            <h3 class="room-card-title">{{ $room->name }}</h3>
                            <p class="room-card-desc">{{ Str::limit($room->description, 90) }}</p>
                            <div class="room-card-meta">
                                <span class="badge badge-blue">{{ $room->capacity }} khách</span>
                                @if ($room->bed_type)
                                    <span class="badge badge-blue">{{ $room->bed_type }}</span>
                                @endif
                            </div>
                            <div class="room-card-footer">
                                <span class="room-card-price">{{ number_format($room->price_per_night, 0, ',', '.') }}đ<span class="text-xs font-medium text-slate-400">/đêm</span></span>
                                <a href="{{ route('rooms.show', $room->id) }}" class="btn-outline btn-sm">Xem chi tiết</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section>
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <span class="section-kicker">Loại phòng</span>
                <h2 class="section-title">Chọn loại phòng phù hợp</h2>
            </div>
            <p class="section-desc max-w-sm">
                {{ $roomTypes->total() }} loại phòng đang mở bán tại {{ $hotel->name }}.
                @if ($hasFilters)
                    <span class="font-semibold text-primary">(Đang lọc)</span>
                @endif
            </p>
        </div>

        @if ($roomTypes->isEmpty())
            <div class="empty-box">
                Không tìm thấy loại phòng phù hợp với bộ lọc.
                <a href="{{ route('home') }}" class="font-semibold text-primary">Xem tất cả</a>
            </div>
        @else
            <div class="room-grid">
                @foreach ($roomTypes as $room)
                    <article class="room-card">
                        <a href="{{ route('rooms.show', $room->id) }}" class="room-card-image" @if ($room->images->first()) style="background-image:url('{{ $room->images->first()->image_url }}')" @endif>
                            @if (! $room->images->first())
                                {{ $room->name }}
                            @endif
                        </a>
                        <div class="room-card-body">
                            <h3 class="room-card-title">{{ $room->name }}</h3>
                            <p class="room-card-desc">{{ Str::limit($room->description, 90) }}</p>
                            <div class="room-card-meta">
                                <span class="badge badge-blue">{{ $room->capacity }} khách</span>
                                @if ($room->bed_type)
                                    <span class="badge badge-blue">{{ $room->bed_type }}</span>
                                @endif
                            </div>
                            <div class="room-card-footer">
                                <span class="room-card-price">{{ number_format($room->price_per_night, 0, ',', '.') }}đ<span class="text-xs font-medium text-slate-400">/đêm</span></span>
                                <a href="{{ route('rooms.show', $room->id) }}" class="btn-outline btn-sm">Xem chi tiết</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($roomTypes->hasPages())
                <div class="mt-8 flex justify-center">{{ $roomTypes->links() }}</div>
            @endif
        @endif
    </section>

    @if ($promotions->isNotEmpty())
        <section>
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <span class="section-kicker">Ưu đãi</span>
                    <h2 class="section-title">Khuyến mãi đang diễn ra</h2>
                </div>
                <a href="{{ route('promotions.index') }}" class="btn-outline">Xem tất cả</a>
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                @foreach ($promotions as $promo)
                    <div class="card border-2 border-dashed border-accent/40">
                        <span class="badge badge-orange">{{ $promo->code }}</span>
                        <h3 class="mt-3 font-heading text-lg font-bold text-slate-900 dark:text-white">{{ $promo->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ Str::limit($promo->description, 80) }}</p>
                        <div class="mt-3 text-xl font-extrabold text-accent">
                            @if ($promo->discount_percent)
                                Giảm {{ (float) $promo->discount_percent }}%
                            @elseif ($promo->discount_amount)
                                Giảm {{ number_format($promo->discount_amount, 0, ',', '.') }}đ
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($news->isNotEmpty())
        <section>
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <span class="section-kicker">Tin tức</span>
                    <h2 class="section-title">Tin tức & Cập nhật</h2>
                </div>
                <a href="{{ route('news.index') }}" class="btn-outline">Xem tất cả</a>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($news as $article)
                    <a href="{{ route('news.show', $article->slug) }}" class="card flex flex-col overflow-hidden !p-0 transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="aspect-video bg-primary-light/50 dark:bg-primary/10">
                            @if ($article->cover_image_url)
                                <img src="{{ $article->cover_image_url }}" class="h-full w-full object-cover" alt="">
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col gap-2 p-4">
                            <span class="text-xs font-semibold text-slate-400">{{ $article->published_at?->format('d/m/Y') }}</span>
                            <h3 class="font-heading text-lg font-bold text-slate-900 dark:text-white">{{ $article->title }}</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ Str::limit($article->excerpt, 100) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($reviews->isNotEmpty())
        <section>
            <span class="section-kicker">Cảm nhận khách hàng</span>
            <h2 class="section-title">Đánh giá khách hàng</h2>

            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($reviews as $review)
                    <div class="card">
                        <div class="text-accent">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ Str::limit($review->comment, 140) ?: 'Trải nghiệm tuyệt vời!' }}</p>
                        <div class="mt-3 text-sm font-bold text-slate-900 dark:text-white">{{ $review->user->name ?? 'Khách Homi' }}</div>
                        <div class="text-xs text-slate-400">{{ $review->roomType->name ?? '' }}</div>
                        @if (! empty($review->images))
                            <div class="mt-2 flex gap-2">
                                @foreach ($review->images as $img)
                                    <img src="{{ asset('storage/' . $img) }}" class="h-16 w-16 rounded-lg object-cover" alt="">
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section>
        <span class="section-kicker">Dịch vụ</span>
        <h2 class="section-title">Trải nghiệm đặt phòng dễ dàng hơn</h2>

        <div class="mt-5 grid gap-5 sm:grid-cols-3">
            <div class="card">
                <div class="mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-primary-light text-2xl">🏨</div>
                <h3 class="mb-2 text-lg font-bold text-slate-900 dark:text-white">Thông tin rõ ràng</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Xem đầy đủ tiện ích, chính sách và hình ảnh thực tế trước khi đặt.</p>
            </div>
            <div class="card">
                <div class="mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-primary-light text-2xl">🛏️</div>
                <h3 class="mb-2 text-lg font-bold text-slate-900 dark:text-white">Loại phòng đa dạng</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Kiểm tra phòng trống theo ngày và đặt nhiều loại phòng trong một đơn.</p>
            </div>
            <div class="card">
                <div class="mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-primary-light text-2xl">💳</div>
                <h3 class="mb-2 text-lg font-bold text-slate-900 dark:text-white">Thanh toán linh hoạt</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Chuyển khoản, ví điện tử, đặt cọc hoặc thanh toán tại khách sạn.</p>
            </div>
        </div>
    </section>
@endsection
