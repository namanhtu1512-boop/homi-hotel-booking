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

                {{-- Danh sách loại phòng + số khách riêng cho từng loại (multi-choice) --}}
                <div class="form-group">
                    <label>Loại phòng &amp; số khách <span style="color:var(--danger)">*</span></label>
                    <div id="items-container">
                        @foreach ($rows as $i => $row)
                            <div class="item-row" style="border:1px solid var(--border); border-radius:10px; padding:12px; margin-bottom:10px;">
                                <div style="display:flex; gap:8px; align-items:flex-start;">
                                    <select name="items[{{ $i }}][room_type_id]" class="item-room-type" required
                                            onchange="updateEstimate()" style="flex:1;">
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
                                    <input type="number" name="items[{{ $i }}][quantity]" class="item-quantity"
                                           min="1" max="10" value="{{ (int) ($row['quantity'] ?? 1) }}" required
                                           onchange="updateEstimate()" style="width:90px;" title="Số phòng">
                                    <button type="button" class="btn btn-outline btn-remove-row"
                                            onclick="removeItemRow(this)" title="Xóa dòng"
                                            style="padding:10px 12px;">✕</button>
                                </div>

                                <div style="display:flex; gap:10px; align-items:center; margin-top:10px; flex-wrap:wrap;">
                                    <label style="font-size:12px; font-weight:700; white-space:nowrap;">Người lớn</label>
                                    <input type="number" name="items[{{ $i }}][adults]" class="item-adults"
                                           min="1" max="50" value="{{ (int) ($row['adults'] ?? 1) }}" required
                                           onchange="updateEstimate()" style="width:75px;">

                                    <label style="font-size:12px; font-weight:700; white-space:nowrap;">Trẻ em</label>
                                    <input type="number" name="items[{{ $i }}][children]" class="item-children"
                                           min="0" max="50" value="{{ (int) ($row['children'] ?? 0) }}"
                                           onchange="updateEstimate()" style="width:75px;">

                                    <span class="item-capacity-hint" style="font-size:12px; color:var(--muted);"></span>
                                </div>

                                <div class="item-capacity-warning" style="display:none; color:var(--danger); font-size:12px; margin-top:6px; font-weight:600;"></div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="addItemRow()">➕ Thêm loại phòng</button>
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

                {{-- Kiểm tra phòng trống: resubmit GET tới chính trang này kèm items + ngày --}}
                <button type="submit" formmethod="GET" formaction="{{ route('customer.bookings.create') }}"
                        class="btn btn-outline btn-block">🔍 Kiểm tra phòng trống</button>

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

    {{-- Cột phải: lưu ý --}}
    <div>
        <div class="card">
            <div class="section-kicker">Lưu ý</div>
            <ul style="margin: 12px 0 0; padding-left: 18px; color: var(--muted); font-size: 14px; line-height: 1.9;">
                <li>Bạn có thể chọn <strong>nhiều loại phòng</strong> trong cùng một đơn (dùng chung ngày nhận/trả).</li>
                <li>Mỗi loại phòng khai báo <strong>số người lớn/trẻ em riêng</strong>, không vượt quá sức chứa của chính loại phòng đó (capacity × số phòng).</li>
                <li>Đơn đặt phòng sẽ ở trạng thái <strong>chờ xác nhận</strong> cho đến khi admin duyệt.</li>
                <li>Bạn sẽ thanh toán khi nhận phòng (Pay at Hotel).</li>
                <li>Để hủy đơn, vào <strong>Đơn của tôi</strong> và chọn Hủy đơn trước ngày nhận phòng.</li>
            </ul>
        </div>
    </div>
</div>

{{-- Template dòng loại phòng (dùng để clone khi thêm dòng mới) --}}
<template id="item-row-template">
    <div class="item-row" style="border:1px solid var(--border); border-radius:10px; padding:12px; margin-bottom:10px;">
        <div style="display:flex; gap:8px; align-items:flex-start;">
            <select name="items[__INDEX__][room_type_id]" class="item-room-type" required onchange="updateEstimate()" style="flex:1;">
                <option value="">-- Chọn loại phòng --</option>
                @foreach ($roomTypes as $type)
                    <option value="{{ $type->id }}"
                            data-price="{{ (float) $type->price_per_night }}"
                            data-capacity="{{ (int) $type->capacity }}">
                        {{ $type->name }} — {{ number_format($type->price_per_night, 0, ',', '.') }}đ/đêm ({{ $type->capacity }} khách/phòng)
                    </option>
                @endforeach
            </select>
            <input type="number" name="items[__INDEX__][quantity]" class="item-quantity"
                   min="1" max="10" value="1" required onchange="updateEstimate()" style="width:90px;" title="Số phòng">
            <button type="button" class="btn btn-outline btn-remove-row" onclick="removeItemRow(this)" title="Xóa dòng" style="padding:10px 12px;">✕</button>
        </div>

        <div style="display:flex; gap:10px; align-items:center; margin-top:10px; flex-wrap:wrap;">
            <label style="font-size:12px; font-weight:700; white-space:nowrap;">Người lớn</label>
            <input type="number" name="items[__INDEX__][adults]" class="item-adults"
                   min="1" max="50" value="1" required onchange="updateEstimate()" style="width:75px;">

            <label style="font-size:12px; font-weight:700; white-space:nowrap;">Trẻ em</label>
            <input type="number" name="items[__INDEX__][children]" class="item-children"
                   min="0" max="50" value="0" onchange="updateEstimate()" style="width:75px;">

            <span class="item-capacity-hint" style="font-size:12px; color:var(--muted);"></span>
        </div>

        <div class="item-capacity-warning" style="display:none; color:var(--danger); font-size:12px; margin-top:6px; font-weight:600;"></div>
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
                warnEl.style.display = 'block';
            } else {
                warnEl.style.display = 'none';
            }
        });

        if (nights > 0 && total > 0) {
            totalEl.textContent  = fmt(total);
            detailEl.textContent = nights + ' đêm';
            boxEl.style.display  = 'block';
        } else {
            boxEl.style.display = 'none';
        }
    };

    [checkInEl, checkOutEl].forEach(el => el.addEventListener('change', updateEstimate));
    updateEstimate();
})();
</script>
@endsection
