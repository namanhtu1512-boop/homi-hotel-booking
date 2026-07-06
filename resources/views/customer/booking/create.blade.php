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
        : [['room_type_id' => null, 'quantity' => 1, 'adults' => 1, 'children' => 0]];
@endphp

<div class="grid gap-5 lg:grid-cols-[1.3fr_0.7fr]">

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
                                                data-capacity="{{ (int) $type->capacity }}"
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
                                <label class="text-xs font-bold whitespace-nowrap">Người lớn</label>
                                <input type="number" name="items[{{ $i }}][adults]" class="item-adults input w-20"
                                       min="1" max="50" value="{{ (int) ($row['adults'] ?? 1) }}" required onchange="updateEstimate()">

                                <label class="text-xs font-bold whitespace-nowrap">Trẻ em</label>
                                <input type="number" name="items[{{ $i }}][children]" class="item-children input w-20"
                                       min="0" max="50" value="{{ (int) ($row['children'] ?? 0) }}" onchange="updateEstimate()">

                                <span class="item-capacity-hint text-xs text-slate-500 dark:text-slate-400"></span>
                            </div>

                            <div class="item-capacity-warning mt-1.5 hidden text-xs font-semibold text-red-500"></div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn-outline btn-sm mt-2" onclick="addItemRow()">➕ Thêm loại phòng</button>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="check_in">Ngày nhận phòng <span class="text-red-500">*</span></label>
                    <input class="input" type="date" id="check_in" name="check_in" value="{{ old('check_in', $checkIn) }}" min="{{ now()->format('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label" for="check_out">Ngày trả phòng <span class="text-red-500">*</span></label>
                    <input class="input" type="date" id="check_out" name="check_out" value="{{ old('check_out', $checkOut) }}" min="{{ now()->addDay()->format('Y-m-d') }}" required>
                </div>
            </div>

            <button type="submit" formmethod="GET" formaction="{{ route('customer.bookings.create') }}" class="btn-outline w-full">🔍 Kiểm tra phòng trống</button>

            @if (! empty($availabilities))
                <div class="alert {{ collect($availabilities)->every(fn ($a) => $a['error'] === null && $a['result']['can_book']) ? 'alert-success' : 'alert-danger' }}">
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
                <label class="form-label" for="promo_codes_text">Mã giảm giá</label>
                <input class="input" type="text" id="promo_codes_text" name="promo_codes_text" value="{{ old('promo_codes_text', is_array(old('promo_codes')) ? implode(', ', old('promo_codes')) : '') }}" placeholder="VD: SUMMER2026 (nhiều mã cách nhau bằng dấu phẩy)">
            </div>

            <div>
                <label class="form-label" for="note">Yêu cầu đặc biệt</label>
                <textarea class="input" id="note" name="note" rows="3" placeholder="Phòng không hút thuốc, tầng cao, ...">{{ old('note') }}</textarea>
            </div>

            <button type="submit" class="btn-primary w-full">Xác nhận đặt phòng</button>
        </form>
    </div>

    <div class="h-fit space-y-5">
        <div class="card">
            <span class="section-kicker">Lưu ý</span>
            <ul class="mt-3 list-disc space-y-2 pl-4 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                <li>Bạn có thể chọn <strong>nhiều loại phòng</strong> trong cùng một đơn (dùng chung ngày nhận/trả).</li>
                <li>Mỗi loại phòng khai báo <strong>số người lớn/trẻ em riêng</strong>, không vượt quá sức chứa của chính loại phòng đó (capacity × số phòng).</li>
                <li>Đơn đặt phòng sẽ ở trạng thái <strong>chờ xác nhận</strong> cho đến khi admin duyệt.</li>
                <li>Mã giảm giá (nếu có) sẽ được trừ trực tiếp vào tổng tiền đơn.</li>
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
                            data-capacity="{{ (int) $type->capacity }}">
                        {{ $type->name }} — {{ number_format($type->price_per_night, 0, ',', '.') }}đ/đêm ({{ $type->capacity }} khách/phòng)
                    </option>
                @endforeach
            </select>
            <input type="number" name="items[__INDEX__][quantity]" class="item-quantity input w-24"
                   min="1" max="10" value="1" required onchange="updateEstimate()" title="Số phòng">
            <button type="button" class="btn-outline btn-sm btn-remove-row" onclick="removeItemRow(this)" title="Xóa dòng">✕</button>
        </div>

        <div class="mt-2.5 flex flex-wrap items-center gap-2.5">
            <label class="text-xs font-bold whitespace-nowrap">Người lớn</label>
            <input type="number" name="items[__INDEX__][adults]" class="item-adults input w-20"
                   min="1" max="50" value="1" required onchange="updateEstimate()">

            <label class="text-xs font-bold whitespace-nowrap">Trẻ em</label>
            <input type="number" name="items[__INDEX__][children]" class="item-children input w-20"
                   min="0" max="50" value="0" onchange="updateEstimate()">

            <span class="item-capacity-hint text-xs text-slate-500 dark:text-slate-400"></span>
        </div>

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

    function fmt(n) { return n.toLocaleString('vi-VN') + 'đ'; }

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
            const opt      = sel.options[sel.selectedIndex];
            const price    = opt ? (parseFloat(opt.dataset.price) || 0) : 0;
            const capacityPerRoom = opt ? (parseInt(opt.dataset.capacity) || 0) : 0;
            const capacity = capacityPerRoom * qty;
            const guests   = adults + children;

            total += price * nights * qty;

            const hintEl = row.querySelector('.item-capacity-hint');
            const warnEl = row.querySelector('.item-capacity-warning');

            if (capacityPerRoom > 0) {
                hintEl.textContent = `(tối đa ${capacity} khách cho ${qty} phòng này)`;
            } else {
                hintEl.textContent = '';
            }

            if (capacityPerRoom > 0 && guests > capacity) {
                warnEl.textContent = `Vượt sức chứa: ${guests} khách > tối đa ${capacity} khách của loại phòng này.`;
                warnEl.classList.remove('hidden');
            } else {
                warnEl.classList.add('hidden');
            }
        });

        if (nights > 0 && total > 0) {
            totalEl.textContent  = fmt(total);
            detailEl.textContent = nights + ' đêm';
            boxEl.classList.remove('hidden');
        } else {
            boxEl.classList.add('hidden');
        }
    };

    [checkInEl, checkOutEl].forEach(el => el.addEventListener('change', updateEstimate));
    updateEstimate();

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
