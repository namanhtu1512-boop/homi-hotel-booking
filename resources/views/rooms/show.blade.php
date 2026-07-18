@extends('layouts.app')

@section('title', $roomType->name . ' · Homi')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($roomType->description ?? ''), 155, '…') ?: $roomType->name . ' tại Homi Hotel.')
@section('banner_tag', 'Chi tiết phòng')
@section('banner_title', $roomType->name)
@section('banner_subtitle', 'Sức chứa ' . $roomType->capacity . ' khách · ' . number_format($roomType->price_per_night, 0, ',', '.') . 'đ / đêm')

@section('content')

@php
    $seasonalRateJs = $seasonalRate ? [
        'start' => $seasonalRate->start_date->toDateString(),
        'end'   => $seasonalRate->end_date->toDateString(),
        'type'  => $seasonalRate->adjustment_type,
        'value' => (float) $seasonalRate->adjustment_value,
    ] : null;
@endphp

<nav class="text-sm text-slate-500 dark:text-slate-400">
    <a href="{{ route('home') }}" class="text-primary hover:underline">Trang chủ</a>
    <span class="mx-2">›</span>
    <a href="{{ route('rooms.index') }}" class="text-primary hover:underline">Danh sách phòng</a>
    <span class="mx-2">›</span>
    <span>{{ $roomType->name }}</span>
</nav>

<div class="grid gap-5 md:grid-cols-[1.2fr_0.8fr]">

    <div class="flex flex-col gap-5">
        <div class="card overflow-hidden !p-0">
            @include('partials._room-gallery', ['images' => $roomType->images, 'alt' => $roomType->name])
        </div>

        <div class="card">
            <span class="section-kicker">Tổng quan</span>
            <h2 class="section-title mb-4">{{ $roomType->name }}</h2>

            <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-primary-light/50 p-3.5 text-center dark:bg-primary/10">
                    <div class="mb-1 text-xl">👥</div>
                    <div class="text-lg font-bold text-primary">{{ $roomType->capacity }} khách</div>
                    <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Khách tối đa</div>
                </div>

                @if ($roomType->bed_type)
                    <div class="rounded-xl bg-primary-light/50 p-3.5 text-center dark:bg-primary/10">
                        <div class="mb-1 text-xl">🛏️</div>
                        <div class="text-sm leading-tight font-bold text-primary">{{ $roomType->bed_type }}</div>
                        <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Loại giường</div>
                    </div>
                @endif

                @if ($roomType->area)
                    <div class="rounded-xl bg-primary-light/50 p-3.5 text-center dark:bg-primary/10">
                        <div class="mb-1 text-xl">📐</div>
                        <div class="text-lg font-bold text-primary">{{ $roomType->area }} m²</div>
                        <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Diện tích</div>
                    </div>
                @endif

                <div class="rounded-xl bg-primary-light/50 p-3.5 text-center dark:bg-primary/10">
                    <div class="mb-1 text-xl">🏨</div>
                    <div class="text-lg font-bold text-primary">{{ $roomType->total_rooms }}</div>
                    <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Tổng số phòng</div>
                </div>
            </div>

            <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                {{ $roomType->description ?: 'Chưa có mô tả chi tiết.' }}
            </p>
        </div>

        @if ($hotel->amenities->isNotEmpty())
            <div class="card">
                @include('partials._amenities-list', ['amenities' => $hotel->amenities, 'title' => 'Tiện nghi khách sạn'])
            </div>
        @endif

        @if ($hotel->check_in_time || $hotel->check_out_time || $hotel->policies)
            <div class="card">
                <span class="section-kicker">Chính sách</span>
                <h3 class="mb-3 text-lg font-bold text-slate-900 dark:text-white">Giờ nhận &amp; trả phòng</h3>

                <div class="info-list">
                    @if ($hotel->check_in_time)
                        <div class="info-item">
                            <span class="label">🕐 Nhận phòng từ</span>
                            <span class="value">{{ $hotel->check_in_time }}</span>
                        </div>
                    @endif
                    @if ($hotel->check_out_time)
                        <div class="info-item">
                            <span class="label">🕛 Trả phòng trước</span>
                            <span class="value">{{ $hotel->check_out_time }}</span>
                        </div>
                    @endif
                </div>

                @if ($hotel->policies)
                    <div class="mt-4 rounded-r-lg border-l-4 border-primary bg-slate-50 p-4 text-sm leading-relaxed whitespace-pre-line text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $hotel->policies }}</div>
                @endif
            </div>
        @endif

        <div class="card">
            <span class="section-kicker">Bản đồ</span>
            <h3 class="mb-3 text-lg font-bold text-slate-900 dark:text-white">Vị trí khách sạn</h3>
            <div class="aspect-video overflow-hidden rounded-xl">
                <iframe
                    src="https://www.google.com/maps?q={{ urlencode($hotel->address) }}&output=embed"
                    class="h-full w-full border-0"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>

        <div class="card">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <span class="section-kicker">Đánh giá</span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                        @if ($reviewSummary['count'] > 0)
                            ★ {{ $reviewSummary['avg'] }}/5 ({{ $reviewSummary['count'] }} đánh giá)
                        @else
                            Chưa có đánh giá
                        @endif
                    </h3>
                </div>
                @auth
                    @if (auth()->user()->role === 'customer')
                        <a href="{{ route('customer.reviews.create') }}" class="btn-outline btn-sm">Viết đánh giá</a>
                    @endif
                @endauth
            </div>

            @if ($reviews->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400">Hãy là người đầu tiên đánh giá loại phòng này.</p>
            @else
                <div class="space-y-4">
                    @foreach ($reviews as $review)
                        <div class="border-t border-slate-200 pt-4 first:border-0 first:pt-0 dark:border-slate-800">
                            <div class="text-accent">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $review->comment }}</p>
                            <div class="mt-1 text-xs font-semibold text-slate-400">{{ $review->user->name ?? 'Khách Homi' }} · {{ $review->created_at->format('d/m/Y') }}</div>
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
            @endif
        </div>
    </div>

    <div class="flex flex-col gap-5">
        <div id="price-sidebar" class="card sticky top-20 {{ $seasonalRate ? 'ring-2 ring-offset-2 dark:ring-offset-slate-950 ' . ($seasonalRate->adjustment_value < 0 ? 'ring-red-500' : 'ring-amber-500') : '' }}">
            <span class="section-kicker">Giá phòng</span>

            @php
                $seasonalPrice = $seasonalRate
                    ? $roomType->price_per_night + ($seasonalRate->adjustment_type === 'percent'
                        ? round($roomType->price_per_night * ((float) $seasonalRate->adjustment_value / 100))
                        : (float) $seasonalRate->adjustment_value)
                    : null;
            @endphp

            @if ($seasonalRate)
                <span class="mb-2 inline-flex animate-pulse items-center gap-1 rounded-full bg-gradient-to-r {{ $seasonalRate->adjustment_value < 0 ? 'from-red-600 to-orange-500' : 'from-amber-600 to-amber-500' }} px-3 py-1.5 text-sm font-extrabold text-white shadow-md">
                    {{ $seasonalRate->adjustment_value < 0 ? '🔥 Giảm giá mùa' : '📈 Phụ thu mùa' }} · {{ $seasonalRate->label }}
                    ({{ $seasonalRate->adjustment_type === 'percent' ? number_format($seasonalRate->adjustment_value, 0) . '%' : number_format($seasonalRate->adjustment_value, 0, ',', '.') . 'đ' }})
                </span>
                <div class="mb-1 text-sm text-slate-400 line-through">{{ number_format($roomType->price_per_night, 0, ',', '.') }}đ</div>
                <div class="mb-1 text-4xl font-extrabold {{ $seasonalRate->adjustment_value < 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($seasonalPrice, 0, ',', '.') }}đ
                </div>
                <div class="mb-5 text-sm text-slate-500 dark:text-slate-400">/ đêm / phòng · áp dụng {{ $seasonalRate->start_date->format('d/m') }} – {{ $seasonalRate->end_date->format('d/m/Y') }}</div>
            @else
                <div class="mb-1 text-3xl font-extrabold text-primary">
                    {{ number_format($roomType->price_per_night, 0, ',', '.') }}đ
                </div>
                <div class="mb-5 text-sm text-slate-500 dark:text-slate-400">/ đêm / phòng</div>
            @endif

            <form method="GET" action="{{ route('rooms.show', $roomType->id) }}" class="space-y-3">
                <div>
                    <label class="form-label" for="check_in">Ngày nhận phòng</label>
                    <input class="input" type="date" id="check_in" name="check_in" value="{{ $checkIn }}" min="{{ now()->format('Y-m-d') }}" required>
                </div>

                <div>
                    <label class="form-label" for="check_out">Ngày trả phòng</label>
                    <input class="input" type="date" id="check_out" name="check_out" value="{{ $checkOut }}" min="{{ now()->addDay()->format('Y-m-d') }}" required>
                </div>

                <div>
                    <label class="form-label" for="quantity">Số phòng</label>
                    <input class="input" type="number" id="quantity" name="quantity" min="1" max="{{ $roomType->total_rooms }}" value="{{ $quantity }}">
                </div>

                <div id="price-estimate" class="hidden rounded-xl bg-primary-light/50 p-3.5 dark:bg-primary/10">
                    <div class="mb-1 text-xs font-semibold tracking-wide text-slate-500 uppercase dark:text-slate-400">Giá tạm tính</div>
                    <div id="price-total" class="text-xl font-extrabold text-primary"></div>
                    <div id="price-detail" class="mt-1 text-xs text-slate-500 dark:text-slate-400"></div>
                </div>

                <button type="submit" class="btn-outline w-full">🔍 Kiểm tra phòng trống</button>
            </form>

            @auth
                @if (auth()->user()->role === 'customer')
                    <form method="POST" action="{{ route('customer.wishlist.store', $roomType->id) }}" class="mt-2">
                        @csrf
                        <input type="hidden" name="quantity" value="{{ $quantity }}">
                        <button type="submit" class="btn-outline w-full">☆ Thêm vào yêu thích</button>
                    </form>
                @endif
            @endauth

            @if ($availabilityError)
                <div class="alert alert-danger mt-3">{{ $availabilityError }}</div>
            @elseif ($availability)
                <div class="alert {{ $availability['can_book'] ? 'alert-success' : 'alert-danger' }} mt-3">
                    @if ($availability['can_book'])
                        ✅ Còn {{ $availability['available_quantity'] }} phòng trống cho {{ $availability['nights'] }} đêm bạn chọn.
                    @else
                        ❌ Chỉ còn {{ $availability['available_quantity'] }} phòng trống, không đủ cho {{ $quantity }} phòng yêu cầu.
                    @endif
                </div>

                @if ($availability['can_book'])
                    @auth
                        @if (in_array(auth()->user()->role, ['admin', 'staff']))
                            <div class="alert alert-danger mt-2 text-xs">
                                ⚠️ Bạn đang đăng nhập với tài khoản <strong>{{ auth()->user()->role }}</strong>.
                                Vui lòng đăng xuất và đăng nhập bằng tài khoản <strong>khách hàng</strong> để đặt phòng.
                            </div>
                        @else
                            <a href="{{ route('customer.bookings.create', ['room_type_id' => $roomType->id, 'check_in' => $checkIn, 'check_out' => $checkOut, 'quantity' => $quantity]) }}" class="btn-primary mt-2 w-full">
                                Đặt phòng ngay →
                            </a>
                        @endif
                    @else
                        <a href="{{ route('customer.bookings.create', ['room_type_id' => $roomType->id, 'check_in' => $checkIn, 'check_out' => $checkOut, 'quantity' => $quantity]) }}" class="btn-primary mt-2 w-full">
                            Đặt phòng ngay →
                        </a>
                    @endauth
                @endif
            @endif

            <div class="mt-4 space-y-1 border-t border-slate-200 pt-3.5 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                <div>✔ Thanh toán tại khách sạn</div>
                <div>✔ Hủy miễn phí trước ngày nhận phòng</div>
                <div>✔ Xác nhận đặt phòng tức thì</div>
            </div>
        </div>

        <div class="card">
            <span class="section-kicker">Về khách sạn</span>
            <h3 class="mb-2 text-lg font-bold text-slate-900 dark:text-white">{{ $hotel->name }}</h3>

            @if ($hotel->star_rating)
                <div class="mb-2 text-accent">
                    {{ str_repeat('★', $hotel->star_rating) }}{{ str_repeat('☆', 5 - $hotel->star_rating) }}
                    <span class="ml-1 text-sm text-slate-500 dark:text-slate-400">{{ $hotel->star_rating }} sao</span>
                </div>
            @endif

            <div class="info-list">
                @if ($hotel->address)
                    <div class="info-item">
                        <span class="label">📍 Địa chỉ</span>
                        <span class="value">{{ $hotel->address }}</span>
                    </div>
                @endif
                @if ($hotel->phone)
                    <div class="info-item">
                        <span class="label">📞 Điện thoại</span>
                        <span class="value">{{ $hotel->phone }}</span>
                    </div>
                @endif
                @if ($hotel->email)
                    <div class="info-item">
                        <span class="label">✉ Email</span>
                        <span class="value">{{ $hotel->email }}</span>
                    </div>
                @endif
            </div>
        </div>

        @if ($relatedRooms->isNotEmpty())
            <div class="card">
                <span class="section-kicker">Có thể bạn thích</span>
                <h3 class="mb-3 text-lg font-bold text-slate-900 dark:text-white">Loại phòng khác</h3>
                <div class="space-y-3">
                    @foreach ($relatedRooms as $related)
                        <a href="{{ route('rooms.show', $related->id) }}" class="flex gap-3 rounded-xl p-2 hover:bg-slate-50 dark:hover:bg-slate-800">
                            <div class="h-16 w-20 shrink-0 overflow-hidden rounded-lg bg-primary-light/50 dark:bg-primary/10">
                                @if ($related->images->first())
                                    <img src="{{ $related->images->first()->image_url }}" class="h-full w-full object-cover" alt="">
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $related->name }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $related->capacity }} khách</div>
                                <div class="text-sm font-bold text-primary">{{ number_format($related->price_per_night, 0, ',', '.') }}đ/đêm</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <a href="{{ route('rooms.index') }}" class="btn-outline w-full text-center">← Xem tất cả loại phòng</a>
    </div>
</div>

<script>
(function () {
    const pricePerNight = {{ (float) $roomType->price_per_night }};
    const seasonalRate  = @json($seasonalRateJs);
    const checkInInput  = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    const quantityInput = document.getElementById('quantity');
    const estimateBox   = document.getElementById('price-estimate');
    const totalEl       = document.getElementById('price-total');
    const detailEl      = document.getElementById('price-detail');

    function formatVnd(n) { return Math.round(n).toLocaleString('vi-VN') + 'đ'; }

    function nightlyPrice(dateStr) {
        if (!seasonalRate || dateStr < seasonalRate.start || dateStr > seasonalRate.end) {
            return pricePerNight;
        }

        return seasonalRate.type === 'percent'
            ? pricePerNight + Math.round(pricePerNight * seasonalRate.value / 100)
            : pricePerNight + seasonalRate.value;
    }

    function updateEstimate() {
        const ci  = checkInInput.value;
        const co  = checkOutInput.value;
        const qty = parseInt(quantityInput.value) || 1;

        if (!ci || !co) { estimateBox.classList.add('hidden'); return; }

        const nights = Math.round((new Date(co) - new Date(ci)) / 86400000);
        if (nights <= 0) { estimateBox.classList.add('hidden'); return; }

        let roomSubtotal = 0;
        const cursor = new Date(ci);
        for (let i = 0; i < nights; i++) {
            roomSubtotal += nightlyPrice(cursor.toISOString().slice(0, 10));
            cursor.setDate(cursor.getDate() + 1);
        }
        roomSubtotal *= qty;

        totalEl.textContent  = formatVnd(roomSubtotal);
        detailEl.textContent = nights + ' đêm × ' + qty + ' phòng (giá tạm tính, có thể lệch nhẹ nếu áp dụng phụ thu cuối tuần)';
        estimateBox.classList.remove('hidden');
    }

    [checkInInput, checkOutInput, quantityInput].forEach(el => el.addEventListener('change', updateEstimate));
    updateEstimate();
})();
</script>
@endsection
