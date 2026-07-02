@extends('layouts.app')

@section('title', $roomType->name . ' · Homi')
@section('banner_tag', 'Chi tiết phòng')
@section('banner_title', $roomType->name)
@section('banner_subtitle', 'Sức chứa ' . $roomType->capacity . ' khách · ' . number_format($roomType->price_per_night, 0, ',', '.') . 'đ / đêm')

@section('content')

{{-- Breadcrumb --}}
<nav style="margin-bottom: 20px; font-size: 14px; color: var(--muted);">
    <a href="{{ route('home') }}" style="color: var(--primary); text-decoration: none;">Trang chủ</a>
    <span style="margin: 0 8px;">›</span>
    <a href="{{ route('rooms.index') }}" style="color: var(--primary); text-decoration: none;">Danh sách phòng</a>
    <span style="margin: 0 8px;">›</span>
    <span>{{ $roomType->name }}</span>
</nav>

<div class="dashboard-grid">

    {{-- ===== CỘT TRÁI ===== --}}
    <div style="display: flex; flex-direction: column; gap: 20px;">

        {{-- Gallery ảnh --}}
        <div class="card" style="padding: 0; overflow: hidden;">
            @include('partials._room-gallery', ['images' => $roomType->images, 'alt' => $roomType->name])
        </div>

        {{-- Tổng quan phòng --}}
        <div class="card">
            <div class="section-kicker">Tổng quan</div>
            <h2 class="section-title" style="margin-bottom: 16px;">{{ $roomType->name }}</h2>

            {{-- Thông số dạng grid --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px;">
                <div style="background: var(--primary-soft); border-radius: 12px; padding: 14px 16px; text-align: center;">
                    <div style="font-size: 22px; margin-bottom: 4px;">👥</div>
                    <div style="font-size: 18px; font-weight: 700; color: var(--primary);">{{ $roomType->capacity }}</div>
                    <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">Khách tối đa</div>
                </div>

                @if ($roomType->bed_type)
                <div style="background: var(--primary-soft); border-radius: 12px; padding: 14px 16px; text-align: center;">
                    <div style="font-size: 22px; margin-bottom: 4px;">🛏️</div>
                    <div style="font-size: 14px; font-weight: 700; color: var(--primary); line-height: 1.3;">{{ $roomType->bed_type }}</div>
                    <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">Loại giường</div>
                </div>
                @endif

                @if ($roomType->area)
                <div style="background: var(--primary-soft); border-radius: 12px; padding: 14px 16px; text-align: center;">
                    <div style="font-size: 22px; margin-bottom: 4px;">📐</div>
                    <div style="font-size: 18px; font-weight: 700; color: var(--primary);">{{ $roomType->area }} m²</div>
                    <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">Diện tích</div>
                </div>
                @endif

                <div style="background: var(--primary-soft); border-radius: 12px; padding: 14px 16px; text-align: center;">
                    <div style="font-size: 22px; margin-bottom: 4px;">🏨</div>
                    <div style="font-size: 18px; font-weight: 700; color: var(--primary);">{{ $roomType->total_rooms }}</div>
                    <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">Tổng số phòng</div>
                </div>
            </div>

            {{-- Mô tả --}}
            <p style="color: var(--muted); font-size: 15px; line-height: 1.8; margin: 0;">
                {{ $roomType->description ?: 'Chưa có mô tả chi tiết.' }}
            </p>
        </div>

        {{-- Tiện nghi khách sạn --}}
        @if ($hotel->amenities->isNotEmpty())
        <div class="card">
            <div class="section-kicker">Tiện nghi khách sạn</div>
            <h3 class="section-title" style="font-size: 18px; margin-bottom: 14px;">Những gì bạn sẽ có</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px;">
                @foreach ($hotel->amenities as $amenity)
                    <div style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; font-size: 14px;">
                        <span style="color: var(--primary); font-size: 16px;">✓</span>
                        <span>{{ $amenity->name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Chính sách --}}
        @if ($hotel->check_in_time || $hotel->check_out_time || $hotel->policies)
        <div class="card">
            <div class="section-kicker">Chính sách</div>
            <h3 class="section-title" style="font-size: 18px; margin-bottom: 14px;">Giờ nhận &amp; trả phòng</h3>

            <div class="info-list">
                @if ($hotel->check_in_time)
                    <div class="info-item">
                        <span class="label">🕐 Nhận phòng từ</span>
                        <span class="value" style="font-weight: 600;">{{ $hotel->check_in_time }}</span>
                    </div>
                @endif
                @if ($hotel->check_out_time)
                    <div class="info-item">
                        <span class="label">🕛 Trả phòng trước</span>
                        <span class="value" style="font-weight: 600;">{{ $hotel->check_out_time }}</span>
                    </div>
                @endif
            </div>

            @if ($hotel->policies)
                <div style="margin-top: 16px; padding: 14px 16px; background: var(--bg); border-left: 3px solid var(--primary); border-radius: 0 8px 8px 0; font-size: 14px; color: var(--muted); line-height: 1.9; white-space: pre-line;">{{ $hotel->policies }}</div>
            @endif
        </div>
        @endif

    </div>

    {{-- ===== CỘT PHẢI ===== --}}
    <div style="display: flex; flex-direction: column; gap: 20px;">

        {{-- Widget đặt phòng --}}
        <div class="card" style="position: sticky; top: 20px;">
            <div class="section-kicker">Giá phòng</div>
            <div style="font-size: 28px; font-weight: 800; color: var(--primary); margin-bottom: 4px;">
                {{ number_format($roomType->price_per_night, 0, ',', '.') }}đ
            </div>
            <div style="font-size: 13px; color: var(--muted); margin-bottom: 20px;">/ đêm / phòng</div>

            <form method="GET" action="{{ route('rooms.show', $roomType->id) }}" class="form-grid">
                <div class="form-group">
                    <label for="check_in">Ngày nhận phòng</label>
                    <input type="date" id="check_in" name="check_in"
                           value="{{ $checkIn }}"
                           min="{{ now()->format('Y-m-d') }}" required>
                </div>

                <div class="form-group">
                    <label for="check_out">Ngày trả phòng</label>
                    <input type="date" id="check_out" name="check_out"
                           value="{{ $checkOut }}"
                           min="{{ now()->addDay()->format('Y-m-d') }}" required>
                </div>

                <div class="form-group">
                    <label for="quantity">Số phòng</label>
                    <input type="number" id="quantity" name="quantity"
                           min="1" max="{{ $roomType->total_rooms }}"
                           value="{{ $quantity }}">
                </div>

                {{-- Ước tính giá --}}
                <div id="price-estimate" style="display:none; background: var(--primary-soft); border: 1px solid var(--border); border-radius: 10px; padding: 14px;">
                    <div style="font-size: 12px; font-weight: 600; color: var(--muted); letter-spacing: .05em; margin-bottom: 6px;">GIÁ TẠM TÍNH</div>
                    <div id="price-total" style="font-size: 20px; font-weight: 800; color: var(--primary);"></div>
                    <div id="price-detail" style="font-size: 12px; color: var(--muted); margin-top: 4px;"></div>
                </div>

                <button type="submit" class="btn btn-outline btn-block">🔍 Kiểm tra phòng trống</button>
            </form>

            {{-- Kết quả availability --}}
            @if ($availabilityError)
                <div class="alert alert-danger" style="margin-top: 12px;">{{ $availabilityError }}</div>
            @elseif ($availability)
                <div class="alert {{ $availability['can_book'] ? 'alert-success' : 'alert-danger' }}" style="margin-top: 12px;">
                    @if ($availability['can_book'])
                        ✅ Còn {{ $availability['available_quantity'] }} phòng trống cho {{ $availability['nights'] }} đêm bạn chọn.
                    @else
                        ❌ Chỉ còn {{ $availability['available_quantity'] }} phòng trống, không đủ cho {{ $quantity }} phòng yêu cầu.
                    @endif
                </div>

                @if ($availability['can_book'])
                    <a href="{{ route('customer.bookings.create', [
                            'room_type_id' => $roomType->id,
                            'check_in'     => $checkIn,
                            'check_out'    => $checkOut,
                            'quantity'     => $quantity,
                        ]) }}"
                       class="btn btn-primary btn-block" style="margin-top: 10px;">
                        Đặt phòng ngay →
                    </a>
                @endif
            @endif

            <div style="margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border); font-size: 13px; color: var(--muted); line-height: 1.7;">
                <div>✔ Thanh toán tại khách sạn</div>
                <div>✔ Hủy miễn phí trước ngày nhận phòng</div>
                <div>✔ Xác nhận đặt phòng tức thì</div>
            </div>
        </div>

        {{-- Thông tin khách sạn --}}
        <div class="card">
            <div class="section-kicker">Về khách sạn</div>
            <h3 class="section-title" style="font-size: 18px; margin-bottom: 8px;">{{ $hotel->name }}</h3>

            @if ($hotel->star_rating)
                <div style="margin-bottom: 10px;">
                    @for ($i = 1; $i <= 5; $i++)
                        <span style="color: {{ $i <= $hotel->star_rating ? '#f5a623' : '#ddd' }}; font-size: 16px;">★</span>
                    @endfor
                    <span style="font-size: 13px; color: var(--muted); margin-left: 4px;">{{ $hotel->star_rating }} sao</span>
                </div>
            @endif

            <div class="info-list">
                @if ($hotel->address)
                    <div class="info-item">
                        <span class="label">📍 Địa chỉ</span>
                        <span class="value">{{ $hotel->address }}</span>
                    </div>
                @endif
                @if ($hotel->hotline)
                    <div class="info-item">
                        <span class="label">📞 Hotline</span>
                        <span class="value">{{ $hotel->hotline }}</span>
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

        {{-- Quay lại danh sách --}}
        <a href="{{ route('rooms.index') }}" class="btn btn-outline btn-block">
            ← Xem tất cả loại phòng
        </a>

    </div>
</div>

<script>
(function () {
    const pricePerNight = {{ (float) $roomType->price_per_night }};
    const checkInInput  = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    const quantityInput = document.getElementById('quantity');
    const estimateBox   = document.getElementById('price-estimate');
    const totalEl       = document.getElementById('price-total');
    const detailEl      = document.getElementById('price-detail');

    function formatVnd(n) { return n.toLocaleString('vi-VN') + 'đ'; }

    function updateEstimate() {
        const ci  = checkInInput.value;
        const co  = checkOutInput.value;
        const qty = parseInt(quantityInput.value) || 1;

        if (!ci || !co) { estimateBox.style.display = 'none'; return; }

        const nights = Math.round((new Date(co) - new Date(ci)) / 86400000);
        if (nights <= 0) { estimateBox.style.display = 'none'; return; }

        totalEl.textContent  = formatVnd(pricePerNight * nights * qty);
        detailEl.textContent = formatVnd(pricePerNight) + ' × ' + nights + ' đêm × ' + qty + ' phòng';
        estimateBox.style.display = 'block';
    }

    [checkInInput, checkOutInput, quantityInput].forEach(el => el.addEventListener('change', updateEstimate));
    updateEstimate();
})();
</script>
@endsection
