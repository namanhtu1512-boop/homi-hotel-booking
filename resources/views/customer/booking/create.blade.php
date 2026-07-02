@extends('layouts.app')

@section('title', 'Đặt phòng · Homi')
@section('banner_tag', 'Đặt phòng')
@section('banner_title', 'Hoàn tất thông tin đặt phòng')
@section('banner_subtitle', 'Kiểm tra lại thông tin trước khi gửi yêu cầu đặt phòng.')

@section('content')
<div class="dashboard-grid">

    {{-- Cột trái: form đặt phòng --}}
    <div>
        <div class="card">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('customer.bookings.store') }}" class="form-grid" id="booking-form">
                @csrf

                {{-- Loại phòng --}}
                <div class="form-group">
                    <label for="room_type_id">Loại phòng <span style="color:var(--danger)">*</span></label>
                    @if ($roomType)
                        <input type="text"
                               value="{{ $roomType->name }} — {{ number_format($roomType->price_per_night, 0, ',', '.') }}đ/đêm"
                               disabled style="background:#f4f8ff; color: var(--muted);">
                        <input type="hidden" name="room_type_id" value="{{ $roomType->id }}"
                               data-price="{{ (float) $roomType->price_per_night }}">
                    @else
                        <select id="room_type_id" name="room_type_id" required
                                onchange="updateRoomPrice(this)">
                            <option value="">-- Chọn loại phòng --</option>
                            @foreach ($roomTypes as $type)
                                <option value="{{ $type->id }}"
                                        data-price="{{ (float) $type->price_per_night }}"
                                        @selected(old('room_type_id') == $type->id)>
                                    {{ $type->name }} — {{ number_format($type->price_per_night, 0, ',', '.') }}đ/đêm
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                {{-- Ngày --}}
                <div class="form-group">
                    <label for="check_in">Ngày nhận phòng <span style="color:var(--danger)">*</span></label>
                    <input type="date" id="check_in" name="check_in"
                           value="{{ old('check_in', $checkIn) }}"
                           min="{{ now()->format('Y-m-d') }}" required>
                </div>

                <div class="form-group">
                    <label for="check_out">Ngày trả phòng <span style="color:var(--danger)">*</span></label>
                    <input type="date" id="check_out" name="check_out"
                           value="{{ old('check_out', $checkOut) }}"
                           min="{{ now()->addDay()->format('Y-m-d') }}" required>
                </div>

                {{-- Số phòng --}}
                <div class="form-group">
                    <label for="quantity">Số phòng <span style="color:var(--danger)">*</span></label>
                    <input type="number" id="quantity" name="quantity"
                           min="1" max="10"
                           value="{{ old('quantity', $quantity) }}" required>
                </div>

                {{-- Kiểm tra phòng trống (Tuần 9 - Sprint 5): resubmit GET tới chính
                     trang này kèm ngày/số lượng, không gửi đơn thật --}}
                <button type="submit" formmethod="GET" formaction="{{ route('customer.bookings.create') }}"
                        class="btn btn-outline btn-block">🔍 Kiểm tra phòng trống</button>

                @if ($availabilityError)
                    <div class="alert alert-danger">{{ $availabilityError }}</div>
                @elseif ($availability)
                    <div class="alert {{ $availability['can_book'] ? 'alert-success' : 'alert-danger' }}">
                        @if ($availability['can_book'])
                            ✅ Còn {{ $availability['available_quantity'] }} phòng trống cho {{ $availability['nights'] }} đêm bạn chọn.
                        @else
                            ❌ Chỉ còn {{ $availability['available_quantity'] }} phòng trống, không đủ cho {{ $availability['requested_quantity'] }} phòng yêu cầu.
                        @endif
                    </div>
                @endif

                {{-- Ước tính giá --}}
                <div id="price-estimate" style="display:none; background: var(--primary-soft); border: 1px solid var(--border); border-radius: 12px; padding: 16px;">
                    <div style="color: var(--muted); font-size: 13px; font-weight: 600; margin-bottom: 6px;">GIÁ TẠM TÍNH</div>
                    <div id="price-total" style="font-size: 22px; font-weight: 800; color: var(--primary);"></div>
                    <div id="price-detail" style="color: var(--muted); font-size: 13px; margin-top: 4px;"></div>
                </div>

                {{-- Thông tin liên hệ --}}
                <div class="section-kicker" style="margin-top: 4px;">Thông tin khách lưu trú</div>

                <div class="form-group">
                    <label for="customer_name">Họ tên khách <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="customer_name" name="customer_name"
                           value="{{ old('customer_name', auth()->user()->name) }}"
                           placeholder="Nguyễn Văn A" required>
                </div>

                <div class="form-group">
                    <label for="customer_phone">Số điện thoại <span style="color:var(--danger)">*</span></label>
                    <input type="tel" id="customer_phone" name="customer_phone"
                           value="{{ old('customer_phone', auth()->user()->phone ?? '') }}"
                           placeholder="09xxxxxxxx" required>
                </div>

                <div class="form-group">
                    <label for="customer_email">Email liên hệ</label>
                    <input type="email" id="customer_email" name="customer_email"
                           value="{{ old('customer_email', auth()->user()->email) }}"
                           placeholder="email@example.com">
                </div>

                <div class="form-group">
                    <label for="note">Ghi chú</label>
                    <textarea id="note" name="note" rows="3"
                              placeholder="Yêu cầu đặc biệt (phòng không hút thuốc, tầng cao, ...)">{{ old('note') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Xác nhận đặt phòng</button>
            </form>
        </div>
    </div>

    {{-- Cột phải: tóm tắt đặt phòng --}}
    <div>
        @if ($roomType)
            <div class="card">
                <div class="section-kicker">Phòng đã chọn</div>
                <h3 class="section-title" style="font-size: 20px; margin-bottom: 8px;">{{ $roomType->name }}</h3>

                @if ($roomType->images->isNotEmpty())
                    <div class="hotel-gallery-main" style="height:160px; background-image: url('{{ $roomType->images->first()->image_url }}');"></div>
                @endif

                <div class="info-list" style="margin-top: 14px;">
                    <div class="info-item">
                        <span class="label">Giá/đêm</span>
                        <span class="value">{{ number_format($roomType->price_per_night, 0, ',', '.') }}đ</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Sức chứa</span>
                        <span class="value">{{ $roomType->capacity }} khách</span>
                    </div>
                    @if ($roomType->bed_type)
                        <div class="info-item">
                            <span class="label">Loại giường</span>
                            <span class="value">{{ $roomType->bed_type }}</span>
                        </div>
                    @endif
                    @if ($roomType->area)
                        <div class="info-item">
                            <span class="label">Diện tích</span>
                            <span class="value">{{ $roomType->area }} m²</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="card">
            <div class="section-kicker">Lưu ý</div>
            <ul style="margin: 12px 0 0; padding-left: 18px; color: var(--muted); font-size: 14px; line-height: 1.9;">
                <li>Đơn đặt phòng sẽ ở trạng thái <strong>chờ xác nhận</strong> cho đến khi admin duyệt.</li>
                <li>Bạn sẽ thanh toán khi nhận phòng (Pay at Hotel).</li>
                <li>Để hủy đơn, vào <strong>Đơn của tôi</strong> và chọn Hủy đơn trước ngày nhận phòng.</li>
            </ul>
        </div>
    </div>
</div>

<script>
(function () {
    let currentPrice = {{ $roomType ? (float) $roomType->price_per_night : 0 }};

    const checkInEl  = document.getElementById('check_in');
    const checkOutEl = document.getElementById('check_out');
    const qtyEl      = document.getElementById('quantity');
    const boxEl      = document.getElementById('price-estimate');
    const totalEl    = document.getElementById('price-total');
    const detailEl   = document.getElementById('price-detail');

    function fmt(n) { return n.toLocaleString('vi-VN') + 'đ'; }

    function updateEstimate() {
        const ci  = checkInEl.value;
        const co  = checkOutEl.value;
        const qty = parseInt(qtyEl.value) || 1;

        if (!ci || !co || currentPrice <= 0) { boxEl.style.display = 'none'; return; }

        const nights = Math.round((new Date(co) - new Date(ci)) / 86400000);
        if (nights <= 0) { boxEl.style.display = 'none'; return; }

        totalEl.textContent  = fmt(currentPrice * nights * qty);
        detailEl.textContent = fmt(currentPrice) + ' × ' + nights + ' đêm × ' + qty + ' phòng';
        boxEl.style.display  = 'block';
    }

    window.updateRoomPrice = function (select) {
        const opt = select.options[select.selectedIndex];
        currentPrice = parseFloat(opt.dataset.price) || 0;
        updateEstimate();
    };

    [checkInEl, checkOutEl, qtyEl].forEach(el => el.addEventListener('change', updateEstimate));
    updateEstimate();
})();
</script>
@endsection
