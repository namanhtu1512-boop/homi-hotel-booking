@extends('layouts.app')

@section('title', 'Đặt phòng · Homi')
@section('banner_tag', 'Đặt phòng')
@section('banner_title', 'Hoàn tất thông tin đặt phòng')
@section('banner_subtitle', 'Chọn một hoặc nhiều loại phòng cho cùng khoảng thời gian lưu trú.')

@section('content')
@php
    // Ưu tiên old() (khi POST lỗi) rồi mới tới $items (khi kiểm tra phòng trống bằng GET).
    $rows = old('items', $items);
    $rows = is_array($rows) && $rows !== []
        ? array_values($rows)
        : [['room_type_id' => null, 'quantity' => 1, 'adults' => 1, 'children' => 0, 'infants' => 0]];
@endphp

<div class="grid gap-5 md:grid-cols-[1.3fr_0.7fr]">

    <div class="card">
        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('customer.bookings.store') }}" class="space-y-5" id="booking-form">
            @csrf

            <div>
                <label class="form-label">Loại phòng &amp; số khách <span class="text-red-500">*</span></label>
                <div id="items-container" class="space-y-3">
                    @foreach ($rows as $i => $row)
                        <div class="item-row rounded-xl border border-slate-200 p-3.5 dark:border-slate-800">
                            <div class="flex items-start gap-2">
                                <select name="items[{{ $i }}][room_type_id]" class="item-room-type input flex-1" required onchange="updateEstimate()">
                                    <option value="">-- Chọn loại phòng --</option>
                                    @foreach ($roomTypes as $type)
                                        <option value="{{ $type->id }}"
                                                data-price="{{ (float) $type->price_per_night }}"
                                                data-price-today="{{ (float) ($todayPrices[$type->id] ?? $type->price_per_night) }}"
                                                data-capacity="{{ (int) $type->capacity }}"
                                                data-category="{{ $type->category }}"
                                                data-extra-bed="{{ $type->supportsExtraBed() ? '1' : '0' }}"
                                                @selected((string) ($row['room_type_id'] ?? '') === (string) $type->id)>
                                            {{ $type->name }} — {{ number_format($type->price_per_night, 0, ',', '.') }}đ/đêm ({{ $type->capacity }} khách/phòng)
                                        </option>
                                    @endforeach
                                </select>
                                <input type="number" name="items[{{ $i }}][quantity]" class="item-quantity input w-24"
                                       min="1" max="10" value="{{ (int) ($row['quantity'] ?? 1) }}" required
                                       onchange="updateEstimate()" title="Số phòng">
                                <button type="button" class="btn-outline btn-sm btn-remove-row" onclick="removeItemRow(this)" title="Xóa dòng">✕</button>
                            </div>

                            <div class="mt-2.5 flex flex-wrap items-center gap-2.5">
                                <label class="text-xs font-bold whitespace-nowrap">Người lớn <span class="font-normal text-slate-400">(≥12 tuổi)</span></label>
                                <input type="number" name="items[{{ $i }}][adults]" class="item-adults input w-20"
                                       min="1" max="50" value="{{ (int) ($row['adults'] ?? 1) }}" required onchange="updateEstimate()">

                                <label class="text-xs font-bold whitespace-nowrap">Trẻ em <span class="font-normal text-slate-400">(6-11 tuổi)</span></label>
                                <input type="number" name="items[{{ $i }}][children]" class="item-children input w-20"
                                       min="0" max="50" value="{{ (int) ($row['children'] ?? 0) }}" onchange="updateEstimate()">

                                <label class="text-xs font-bold whitespace-nowrap">Sơ sinh <span class="font-normal text-slate-400">(0-5 tuổi)</span></label>
                                <input type="number" name="items[{{ $i }}][infants]" class="item-infants input w-20"
                                       min="0" max="50" value="{{ (int) ($row['infants'] ?? 0) }}" onchange="updateEstimate()">

                                <span class="item-capacity-hint text-xs text-slate-500 dark:text-slate-400"></span>

                                <span class="w-full text-xs text-slate-500 dark:text-slate-400">Sơ sinh miễn phí, không tính vào sức chứa — tối đa 2 trẻ/phòng.</span>
                            </div>

                            <label class="item-extra-bed-wrap mt-2 hidden items-center gap-1.5 text-xs font-bold">
                                <input type="checkbox" name="items[{{ $i }}][extra_bed]" class="item-extra-bed" value="1"
                                       @checked(! empty($row['extra_bed'])) onchange="updateEstimate()">
                                Cần giường phụ? <span class="font-normal text-slate-400">(chỉ dành cho trẻ em 6-11 tuổi vượt sức chứa, không dùng cho người lớn, tối đa 1 giường/phòng, phụ thu {{ number_format($extraBedSurchargePerNight, 0, ',', '.') }}đ/giường/đêm)</span>
                                @if ($extraBedsAvailable !== null)
                                    <span class="font-normal {{ $extraBedsAvailable > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-red-500' }}">— còn {{ $extraBedsAvailable }} giường phụ trống trong khoảng ngày đã chọn</span>
                                @endif
                            </label>

                            <div class="item-capacity-warning mt-1.5 hidden text-xs font-semibold text-red-500"></div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn-outline btn-sm mt-2" onclick="addItemRow()">➕ Thêm loại phòng</button>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="check_in">Ngày nhận phòng <span class="text-red-500">*</span></label>
                    <input class="input" type="date" id="check_in" name="check_in" value="{{ old('check_in', $checkIn) }}" min="{{ now('Asia/Ho_Chi_Minh')->format('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label" for="check_out">Ngày trả phòng <span class="text-red-500">*</span></label>
                    <input class="input" type="date" id="check_out" name="check_out" value="{{ old('check_out', $checkOut) }}" min="{{ now()->addDay()->format('Y-m-d') }}" required>
                </div>
            </div>

            <button type="submit" formmethod="GET" formaction="{{ route('customer.bookings.create') }}" formnovalidate class="btn-outline w-full">🔍 Kiểm tra phòng trống</button>

            @if (! empty($availabilities))
                @php
                    $allAvailable = collect($availabilities)->every(fn ($a) => $a['error'] === null && $a['result']['can_book']);
                @endphp
                <div class="alert {{ $allAvailable ? 'alert-success' : 'alert-danger' }}" id="availability-alert" data-all-available="{{ $allAvailable ? '1' : '0' }}">
                    @unless ($allAvailable)
                        <div class="mb-2 font-bold">⚠️ Một hoặc nhiều loại phòng bạn chọn đã hết hoặc không đủ số lượng — vui lòng điều chỉnh số phòng/loại phòng bên trên rồi bấm "Kiểm tra phòng trống" lại trước khi đặt.</div>
                    @endunless
                    @foreach ($availabilities as $a)
                        <div>
                            <strong>{{ $a['name'] }}:</strong>
                            @if ($a['error'])
                                {{ $a['error'] }}
                            @elseif ($a['result']['can_book'])
                                ✅ Còn {{ $a['result']['available_quantity'] }} phòng trống cho {{ $a['result']['nights'] }} đêm.
                            @else
                                ❌ Chỉ còn {{ $a['result']['available_quantity'] }} phòng trống, không đủ cho {{ $a['result']['requested_quantity'] }} phòng yêu cầu.
                            @endif

                            @if ($a['pricing'])
                                @php
                                    $seasonalNights = collect($a['pricing']['nightly_breakdown'])->filter(fn ($n) => (float) $n['seasonal_adjustment'] !== 0.0);
                                    $hasDiscount = $seasonalNights->contains(fn ($n) => $n['seasonal_adjustment'] < 0);
                                    $hasSurcharge = $seasonalNights->contains(fn ($n) => $n['seasonal_adjustment'] > 0);
                                @endphp
                                @if ($seasonalNights->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach ($seasonalNights as $n)
                                            <span class="inline-flex animate-pulse items-center gap-1 rounded-full bg-gradient-to-r {{ $n['seasonal_adjustment'] < 0 ? 'from-red-600 to-orange-500' : 'from-amber-600 to-amber-500' }} px-2.5 py-1 text-xs font-extrabold text-white shadow-md">
                                                {{ $n['seasonal_adjustment'] < 0 ? '🔥' : '📈' }}
                                                {{ \Carbon\Carbon::parse($n['date'])->format('d/m') }}: {{ $n['seasonal_adjustment'] > 0 ? '+' : '' }}{{ number_format($n['seasonal_adjustment'], 0, ',', '.') }}đ/đêm
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="mt-2 text-base font-extrabold {{ $hasDiscount && ! $hasSurcharge ? 'text-green-600' : ($hasSurcharge && ! $hasDiscount ? 'text-red-600' : '') }}">Tạm tính (đã gồm điều chỉnh mùa/cuối tuần): {{ number_format($a['pricing']['total_price'], 0, ',', '.') }}đ</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($holdExpiresAt)
                <div class="alert alert-warning" id="hold-countdown-banner">
                    ⏳ Phòng đang được <strong>giữ tạm</strong> cho bạn — vui lòng hoàn tất đặt phòng trong
                    <strong id="hold-countdown-timer"></strong>, sau đó phòng sẽ được mở lại cho khách khác.
                </div>
            @endif

            <div id="price-estimate" class="hidden rounded-xl bg-primary-light/50 p-4 dark:bg-primary/10">
                <div class="mb-1 text-xs font-semibold text-slate-500 uppercase dark:text-slate-400">Giá tạm tính</div>
                <div id="price-total" class="text-2xl font-extrabold text-primary"></div>
                <div id="price-detail" class="mt-1 text-xs text-slate-500 dark:text-slate-400"></div>
            </div>

            <div>
                <span class="section-kicker">Thông tin khách lưu trú</span>
            </div>

            <div>
                <label class="form-label" for="customer_name">Họ tên khách <span class="text-red-500">*</span></label>
                <input class="input" type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', auth()->user()->name) }}" placeholder="Nguyễn Văn A" required>
            </div>

            <div>
                <label class="form-label" for="customer_phone">Số điện thoại <span class="text-red-500">*</span></label>
                <input class="input" type="tel" id="customer_phone" name="customer_phone" value="{{ old('customer_phone', auth()->user()->phone ?? '') }}" placeholder="09xxxxxxxx" required>
            </div>

            <div>
                <label class="form-label" for="customer_email">Email liên hệ</label>
                <input class="input" type="email" id="customer_email" name="customer_email" value="{{ old('customer_email', auth()->user()->email) }}" placeholder="email@example.com">
            </div>

            <div>
                <label class="form-label" for="national_id">Số CCCD/CMND <span class="text-red-500">*</span></label>
                <input class="input" type="text" inputmode="numeric" id="national_id" name="national_id" value="{{ old('national_id', auth()->user()->national_id ?? '') }}" placeholder="9 hoặc 12 chữ số" maxlength="20" required>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Dùng để đối chiếu khi nhận phòng.</p>
            </div>

            <div>
                <label class="form-label" for="promo_codes_text">Mã giảm giá</label>
                <input class="input" type="text" id="promo_codes_text" name="promo_codes_text" value="{{ old('promo_codes_text', is_array(old('promo_codes')) ? implode(', ', old('promo_codes')) : '') }}" placeholder="VD: SUMMER2026 (nhiều mã cách nhau bằng dấu phẩy)">
            </div>

            <div>
                <label class="form-label" for="note">Yêu cầu đặc biệt</label>
                <textarea class="input" id="note" name="note" rows="3" placeholder="Phòng không hút thuốc, tầng cao, ...">{{ old('note') }}</textarea>
            </div>

            <button type="submit" id="submit-booking-btn" class="btn-primary w-full">Xác nhận đặt phòng</button>
        </form>
    </div>

    <div class="h-fit space-y-5">
        <div class="card">
            <span class="section-kicker">Lưu ý</span>
            <ul class="mt-3 list-disc space-y-2 pl-4 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                <li>Bạn có thể chọn <strong>nhiều loại phòng</strong> trong cùng một đơn (dùng chung ngày nhận/trả).</li>
                <li>Mỗi loại phòng khai báo <strong>số người lớn/trẻ em/sơ sinh riêng</strong>, không vượt quá sức chứa của chính loại phòng đó (capacity × số phòng). Người lớn ≥12 tuổi <strong>luôn bị giới hạn đúng bằng sức chứa, không có ngoại lệ</strong>; trẻ em 6-11 tuổi (tối đa 2 trẻ/phòng) nếu vượt giới hạn có thể bù thêm 1 trẻ bằng <strong>giường phụ</strong> có phụ thu (áp dụng mọi loại phòng Standard/Superior/Deluxe/Suite/Family — riêng Family, người lớn tối đa 4 và trẻ em tối đa 2-3 (nếu có giường phụ) được tính <strong>độc lập</strong> với nhau, không cộng chung vào 1 sức chứa như các loại phòng khác); trẻ sơ sinh 0-5 tuổi ở miễn phí, không tính vào sức chứa, nhưng tối đa <strong>2 trẻ sơ sinh/phòng</strong> (áp dụng mọi loại phòng, không có giường phụ bù cho trẻ sơ sinh).</li>
                <li>Đơn đặt phòng được <strong>xác nhận ngay</strong> sau khi đặt — bạn có thể thanh toán luôn mà không cần chờ admin duyệt (trừ khi đơn cần giường phụ nhưng tạm hết, khi đó đơn sẽ ở trạng thái "chờ tư vấn" để bạn chọn phương án khác).</li>
                <li>Mã giảm giá (nếu có) sẽ được trừ trực tiếp vào tổng tiền đơn.</li>
                <li>Dịch vụ thêm (ăn sáng, giặt ủi...) được đặt <strong>sau khi nhận phòng</strong>, không chọn ở bước này.</li>
                <li>Để hủy đơn, vào <strong>Đơn của tôi</strong> và chọn Hủy đơn trước ngày nhận phòng.</li>
            </ul>
        </div>
    </div>
</div>

<template id="item-row-template">
    <div class="item-row rounded-xl border border-slate-200 p-3.5 dark:border-slate-800">
        <div class="flex items-start gap-2">
            <select name="items[__INDEX__][room_type_id]" class="item-room-type input flex-1" required onchange="updateEstimate()">
                <option value="">-- Chọn loại phòng --</option>
                @foreach ($roomTypes as $type)
                    <option value="{{ $type->id }}"
                            data-price="{{ (float) $type->price_per_night }}"
                            data-price-today="{{ (float) ($todayPrices[$type->id] ?? $type->price_per_night) }}"
                            data-capacity="{{ (int) $type->capacity }}"
                            data-category="{{ $type->category }}"
                            data-extra-bed="{{ $type->supportsExtraBed() ? '1' : '0' }}">
                        {{ $type->name }} — {{ number_format($type->price_per_night, 0, ',', '.') }}đ/đêm ({{ $type->capacity }} khách/phòng)
                    </option>
                @endforeach
            </select>
            <input type="number" name="items[__INDEX__][quantity]" class="item-quantity input w-24"
                   min="1" max="10" value="1" required onchange="updateEstimate()" title="Số phòng">
            <button type="button" class="btn-outline btn-sm btn-remove-row" onclick="removeItemRow(this)" title="Xóa dòng">✕</button>
        </div>

        <div class="mt-2.5 flex flex-wrap items-center gap-2.5">
            <label class="text-xs font-bold whitespace-nowrap">Người lớn <span class="font-normal text-slate-400">(≥12 tuổi)</span></label>
            <input type="number" name="items[__INDEX__][adults]" class="item-adults input w-20"
                   min="1" max="50" value="1" required onchange="updateEstimate()">

            <label class="text-xs font-bold whitespace-nowrap">Trẻ em <span class="font-normal text-slate-400">(6-11 tuổi)</span></label>
            <input type="number" name="items[__INDEX__][children]" class="item-children input w-20"
                   min="0" max="50" value="0" onchange="updateEstimate()">

            <label class="text-xs font-bold whitespace-nowrap">Sơ sinh <span class="font-normal text-slate-400">(0-5 tuổi)</span></label>
            <input type="number" name="items[__INDEX__][infants]" class="item-infants input w-20"
                   min="0" max="50" value="0" onchange="updateEstimate()">

            <span class="item-capacity-hint text-xs text-slate-500 dark:text-slate-400"></span>

            <span class="w-full text-xs text-slate-500 dark:text-slate-400">Sơ sinh miễn phí, không tính vào sức chứa — tối đa 2 trẻ/phòng.</span>
        </div>

        <label class="item-extra-bed-wrap mt-2 hidden items-center gap-1.5 text-xs font-bold">
            <input type="checkbox" name="items[__INDEX__][extra_bed]" class="item-extra-bed" value="1" onchange="updateEstimate()">
            Cần giường phụ? <span class="font-normal text-slate-400">(chỉ dành cho trẻ em 6-11 tuổi vượt sức chứa, không dùng cho người lớn, tối đa 1 giường/phòng, phụ thu {{ number_format($extraBedSurchargePerNight, 0, ',', '.') }}đ/giường/đêm)</span>
            @if ($extraBedsAvailable !== null)
                <span class="font-normal {{ $extraBedsAvailable > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-red-500' }}">— còn {{ $extraBedsAvailable }} giường phụ trống trong khoảng ngày đã chọn</span>
            @endif
        </label>

        <div class="item-capacity-warning mt-1.5 hidden text-xs font-semibold text-red-500"></div>
    </div>
</template>

<script>
(function () {
    const container = document.getElementById('items-container');
    const template  = document.getElementById('item-row-template');
    let nextIndex = {{ count($rows) }};

    const checkInEl  = document.getElementById('check_in');
    const checkOutEl = document.getElementById('check_out');
    const boxEl      = document.getElementById('price-estimate');
    const totalEl    = document.getElementById('price-total');
    const detailEl   = document.getElementById('price-detail');
    const extraBedSurchargePerNight = {{ (int) $extraBedSurchargePerNight }};

    function fmt(n) { return Math.round(n).toLocaleString('vi-VN') + 'đ'; }

    function nightsBetween() {
        if (!checkInEl.value || !checkOutEl.value) return 0;
        return Math.round((new Date(checkOutEl.value) - new Date(checkInEl.value)) / 86400000);
    }

    window.addItemRow = function () {
        const html = template.innerHTML.replace(/__INDEX__/g, nextIndex++);
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        container.appendChild(wrap.firstChild);
        updateEstimate();
    };

    window.removeItemRow = function (btn) {
        if (container.querySelectorAll('.item-row').length <= 1) return;
        btn.closest('.item-row').remove();
        updateEstimate();
    };

    window.updateEstimate = function () {
        const nights = nightsBetween();
        let total = 0;

        container.querySelectorAll('.item-row').forEach(row => {
            const sel      = row.querySelector('.item-room-type');
            const qty      = parseInt(row.querySelector('.item-quantity').value) || 0;
            const adults   = parseInt(row.querySelector('.item-adults').value) || 0;
            const children = parseInt(row.querySelector('.item-children').value) || 0;
            const infants  = parseInt(row.querySelector('.item-infants').value) || 0;
            const opt      = sel.options[sel.selectedIndex];
            // Ưu tiên giá đã áp giá theo mùa của HÔM NAY (data-price-today,
            // do server tính sẵn) để khớp với khối "Kiểm tra phòng trống"
            // bên dưới — vẫn chỉ là ước tính nhanh, không đảm bảo khớp 100%
            // khi kỳ nghỉ vắt qua nhiều mức giá/cuối tuần khác nhau.
            const price    = opt ? (parseFloat(opt.dataset.priceToday ?? opt.dataset.price) || 0) : 0;
            const capacityPerRoom = opt ? (parseInt(opt.dataset.capacity) || 0) : 0;
            const supportsExtraBed = opt ? (opt.dataset.extraBed === '1') : false;
            const isFamily = opt ? (opt.dataset.category === 'family') : false;
            const capacity = capacityPerRoom * qty;
            const guests   = adults + children; // trẻ sơ sinh (infants) không tính vào sức chứa
            const maxChildren = 2 * qty;
            const maxInfants  = 2 * qty;
            // Family: người lớn/trẻ em xét RIÊNG, giường phụ chỉ tăng giới hạn
            // trẻ em — khác Standard/Superior/Deluxe/Suite (giường phụ bù
            // TỔNG khách vượt sức chứa). Khớp BookingService::extraBedsNeeded().
            const excess = isFamily ? (children - maxChildren) : (guests - capacity);

            total += price * nights * qty;

            const hintEl        = row.querySelector('.item-capacity-hint');
            const warnEl        = row.querySelector('.item-capacity-warning');
            const extraBedWrap  = row.querySelector('.item-extra-bed-wrap');
            const extraBedInput = row.querySelector('.item-extra-bed');

            if (isFamily) {
                hintEl.textContent = `(tối đa ${capacity} người lớn, tối đa ${maxChildren} trẻ em/phòng cho ${qty} phòng này, chưa kể sơ sinh)`;
            } else if (capacityPerRoom > 0) {
                hintEl.textContent = `(tối đa ${capacity} khách cho ${qty} phòng này, chưa kể sơ sinh)`;
            } else {
                hintEl.textContent = '';
            }

            // Chỉ loại phòng có RoomType::supportsExtraBed() = true mới có
            // tùy chọn giường phụ — ẩn hẳn checkbox + bỏ tick nếu đổi sang loại
            // phòng khác, tránh gửi extra_bed=1 "mồ côi" không hợp lệ với
            // BookingService::validateGuestCapacity().
            if (extraBedWrap) {
                if (supportsExtraBed) {
                    extraBedWrap.classList.remove('hidden');
                    extraBedWrap.classList.add('flex');
                } else {
                    extraBedWrap.classList.add('hidden');
                    extraBedWrap.classList.remove('flex');
                    if (extraBedInput) extraBedInput.checked = false;
                }
            }

            const extraBedChecked = !!(extraBedInput && extraBedInput.checked);
            // Người lớn LUÔN bị chặn cứng theo capacity — giường phụ không
            // bao giờ bù được cho người lớn, chỉ dành cho trẻ em (khớp
            // BookingService::validateGuestCapacity()).
            const adultsWithinCapacity = capacityPerRoom === 0 || adults <= capacity;

            // Chỉ cộng phụ thu vào ước tính khi đủ điều kiện bù qua giường phụ
            // (đúng điều kiện server dùng để không chặn ở validateGuestCapacity())
            // — số giường THẬT SỰ được cấp vẫn do BookingService::create() quyết
            // theo tồn kho lúc đặt, đây chỉ là ước tính hiển thị trước.
            if (supportsExtraBed && extraBedChecked && adultsWithinCapacity && excess <= qty && excess > 0) {
                total += extraBedSurchargePerNight * qty * nights;
            }

            if (capacityPerRoom > 0 && adults > capacity) {
                warnEl.textContent = `Vượt số người lớn: tối đa ${capacity} người lớn cho ${qty} phòng này. Giường phụ chỉ dành cho trẻ em (6-11 tuổi), không dùng để thêm người lớn.`;
                warnEl.classList.remove('hidden');
            } else if (infants > maxInfants) {
                // Trẻ sơ sinh: giới hạn đồng nhất mọi category, không có
                // giường phụ/ngoại lệ nào bù được (khớp validateGuestCapacity()).
                warnEl.textContent = `Vượt giới hạn trẻ sơ sinh: tối đa 2 trẻ sơ sinh (0-5 tuổi)/phòng × ${qty} phòng = ${maxInfants}.`;
                warnEl.classList.remove('hidden');
            } else if (isFamily) {
                // Family: trẻ em vượt giới hạn cơ bản (2/phòng) bù được bằng
                // giường phụ (tăng lên tối đa 3/phòng), độc lập với người lớn.
                if (children > maxChildren) {
                    if (extraBedChecked && excess <= qty) {
                        warnEl.classList.add('hidden');
                    } else {
                        warnEl.textContent = `Vượt giới hạn trẻ em: tối đa ${maxChildren} trẻ em (6-11 tuổi)/phòng × ${qty} phòng. Tick "Cần giường phụ" ở trên để tăng lên tối đa ${maxChildren + qty} trẻ em.`;
                        warnEl.classList.remove('hidden');
                    }
                } else {
                    warnEl.classList.add('hidden');
                }
            } else if (children > maxChildren) {
                warnEl.textContent = `Vượt giới hạn trẻ em: tối đa 2 trẻ em (6-11 tuổi)/phòng × ${qty} phòng = ${maxChildren}.`;
                warnEl.classList.remove('hidden');
            } else if (capacityPerRoom > 0 && guests > capacity) {
                if (supportsExtraBed && extraBedChecked && excess <= qty) {
                    // Đủ điều kiện bù qua giường phụ — không chặn ở đây, server
                    // sẽ tự quyết xác nhận ngay hay chuyển "chờ tư vấn" tùy tồn kho.
                    warnEl.classList.add('hidden');
                } else {
                    warnEl.textContent = supportsExtraBed
                        ? `Vượt sức chứa: ${guests} khách > tối đa ${capacity} khách của loại phòng này. Tick "Cần giường phụ" ở trên để bù phần trẻ em vượt.`
                        : `Vượt sức chứa: ${guests} khách > tối đa ${capacity} khách của loại phòng này.`;
                    warnEl.classList.remove('hidden');
                }
            } else {
                warnEl.classList.add('hidden');
            }
        });

        if (nights > 0 && total > 0) {
            totalEl.textContent  = fmt(total);
            detailEl.textContent = nights + ' đêm (đã gồm điều chỉnh giá theo mùa, có thể lệch nhẹ nếu áp dụng phụ thu cuối tuần)';
            boxEl.classList.remove('hidden');
        } else {
            boxEl.classList.add('hidden');
        }
    };

    // Điều kiện: ngày trả phòng luôn phải sau ngày nhận phòng — trước đây
    // "min" của ô check_out cố định (ngày mai), không theo check_in, nên
    // khách chọn check_in xa rồi vẫn chọn được check_out sớm hơn (chỉ bị
    // chặn khi submit lên server). Cập nhật "min" động ngay khi đổi check_in.
    function syncCheckOutMin() {
        if (!checkInEl.value) return;
        const nextDay = new Date(checkInEl.value);
        nextDay.setDate(nextDay.getDate() + 1);
        const minStr = nextDay.toISOString().slice(0, 10);
        checkOutEl.min = minStr;
        if (checkOutEl.value && checkOutEl.value < minStr) {
            checkOutEl.value = minStr;
        }
    }

    checkInEl.addEventListener('change', syncCheckOutMin);
    syncCheckOutMin();

    [checkInEl, checkOutEl].forEach(el => el.addEventListener('change', updateEstimate));
    updateEstimate();

    // Chặn đặt phòng khi lần kiểm tra gần nhất báo thiếu/hết phòng — trước
    // đây khách vẫn bấm "Xác nhận đặt phòng" được bình thường dù banner đỏ
    // đang báo hết phòng, chỉ bị chặn khi server validate lại (UX rối, dễ
    // hiểu nhầm là bug). Đổi số lượng/loại phòng thì mở khóa lại để khách
    // kiểm tra phòng trống lần nữa với lựa chọn mới.
    (function () {
        const alertEl  = document.getElementById('availability-alert');
        const submitBtn = document.getElementById('submit-booking-btn');
        if (!alertEl || !submitBtn) return;

        if (alertEl.dataset.allAvailable === '0') {
            submitBtn.disabled = true;
            submitBtn.title = 'Vui lòng điều chỉnh và kiểm tra lại phòng trống trước khi đặt.';
        }

        container.addEventListener('change', () => {
            submitBtn.disabled = false;
            submitBtn.title = '';
        });
    })();

    @if ($holdExpiresAt)
        (function () {
            const expiresAt = new Date(@json($holdExpiresAt->toIso8601String())).getTime();
            const timerEl   = document.getElementById('hold-countdown-timer');
            const bannerEl  = document.getElementById('hold-countdown-banner');

            function tick() {
                const remainingMs = expiresAt - Date.now();
                if (remainingMs <= 0) {
                    timerEl.textContent = '0:00';
                    bannerEl.classList.add('alert-danger');
                    return;
                }
                const totalSeconds = Math.floor(remainingMs / 1000);
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = totalSeconds % 60;
                timerEl.textContent = minutes + ':' + String(seconds).padStart(2, '0');
                setTimeout(tick, 1000);
            }

            tick();
        })();
    @endif
})();
</script>
@endsection
