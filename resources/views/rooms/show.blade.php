@extends('layouts.app')

@section('title', $roomType->name . ' · Homi')
@section('banner_tag', 'Chi tiết phòng')
@section('banner_title', $roomType->name)
@section('banner_subtitle', 'Sức chứa ' . $roomType->capacity . ' khách · ' . number_format($roomType->price_per_night, 0, ',', '.') . 'đ / đêm')

@section('content')
<div class="dashboard-grid">

    {{-- Cột trái: thông tin chi tiết phòng --}}
    <div>
        <div class="card">
            {{-- Gallery ảnh phòng --}}
            @include('partials._room-gallery', ['images' => $roomType->images, 'alt' => $roomType->name])

            {{-- Mô tả --}}
            <div class="section-kicker" style="margin-top: 22px;">Mô tả</div>
            <h2 class="section-title" style="margin-bottom: 6px;">{{ $roomType->name }}</h2>
            <p class="section-desc">{{ $roomType->description ?: 'Chưa có mô tả chi tiết.' }}</p>

            {{-- Thông số phòng --}}
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

            {{-- Tiện nghi khách sạn --}}
            @include('partials._amenities-list', ['amenities' => $hotel->amenities, 'title' => 'Tiện nghi khách sạn'])
        </div>

        {{-- Chính sách khách sạn --}}
        @if ($hotel->check_in_time || $hotel->check_out_time || $hotel->policies)
            <div class="card">
                <div class="section-kicker">Chính sách &amp; Giờ nhận/trả phòng</div>

                <div class="info-list" style="margin-top: 12px;">
                    @if ($hotel->check_in_time)
                        <div class="info-item">
                            <span class="label">Nhận phòng từ</span>
                            <span class="value">{{ $hotel->check_in_time }}</span>
                        </div>
                    @endif
                    @if ($hotel->check_out_time)
                        <div class="info-item">
                            <span class="label">Trả phòng trước</span>
                            <span class="value">{{ $hotel->check_out_time }}</span>
                        </div>
                    @endif
                </div>

                @if ($hotel->policies)
                    <div style="margin-top: 14px; color: var(--muted); font-size: 14px; line-height: 1.8; white-space: pre-line;">{{ $hotel->policies }}</div>
                @endif
            </div>
        @endif
    </div>

    {{-- Cột phải: kiểm tra phòng trống + đặt phòng --}}
    <div>
        <div class="card">
            <div class="section-kicker">Đặt phòng</div>
            <h2 class="section-title" style="margin-bottom: 14px;">
                {{ number_format($roomType->price_per_night, 0, ',', '.') }}đ / đêm
            </h2>

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

                {{-- Ước tính giá tạm tính --}}
                <div id="price-estimate" style="display:none; background: var(--primary-soft); border: 1px solid var(--border); border-radius: 12px; padding: 12px 14px; font-size: 14px; color: var(--text);">
                    <span class="label" style="color: var(--muted);">Ước tính tạm tính:</span>
                    <strong id="price-total" style="color: var(--primary); font-size: 16px; display: block; margin-top: 4px;"></strong>
                    <span id="price-detail" style="color: var(--muted); font-size: 13px;"></span>
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
                    <a href="{{ route('customer.bookings.create', [
                            'room_type_id' => $roomType->id,
                            'check_in'     => $checkIn,
                            'check_out'    => $checkOut,
                            'quantity'     => $quantity,
                        ]) }}"
                       class="btn btn-primary btn-block" style="margin-top: 10px;">
                        Đặt phòng ngay
                    </a>
                @endif
            @endif
        </div>

        {{-- Thông tin khách sạn tóm tắt --}}
        <div class="card">
            <div class="section-kicker">Về khách sạn</div>
            <h3 class="section-title" style="font-size: 20px; margin-bottom: 6px;">{{ $hotel->name }}</h3>
            @if ($hotel->address)
                <p class="section-desc" style="margin-bottom: 12px;">{{ $hotel->address }}</p>
            @endif
            @if ($hotel->star_rating)
                <div>
                    @for ($i = 1; $i <= 5; $i++)
                        <span style="color: {{ $i <= $hotel->star_rating ? '#f5a623' : '#ddd' }}; font-size: 18px;">★</span>
                    @endfor
                </div>
            @endif
        </div>
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

    function formatVnd(n) {
        return n.toLocaleString('vi-VN') + 'đ';
    }

    function updateEstimate() {
        const ci = checkInInput.value;
        const co = checkOutInput.value;
        const qty = parseInt(quantityInput.value) || 1;

        if (!ci || !co) { estimateBox.style.display = 'none'; return; }

        const inDate  = new Date(ci);
        const outDate = new Date(co);
        const nights  = Math.round((outDate - inDate) / 86400000);

        if (nights <= 0) { estimateBox.style.display = 'none'; return; }

        const total = pricePerNight * nights * qty;
        totalEl.textContent  = formatVnd(total);
        detailEl.textContent = formatVnd(pricePerNight) + ' × ' + nights + ' đêm × ' + qty + ' phòng';
        estimateBox.style.display = 'block';
    }

    [checkInInput, checkOutInput, quantityInput].forEach(el => el.addEventListener('change', updateEstimate));
    updateEstimate();
})();
</script>
@endsection
