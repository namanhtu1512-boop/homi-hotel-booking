@extends('layouts.staff')

@section('title', 'Chi tiết yêu cầu đoàn · Homi Staff')
@section('page_title', 'Yêu cầu đặt đoàn/nhóm')
@section('page_subtitle', 'Xem thông tin và tạo đơn đặt phòng thủ công từ yêu cầu này.')

@section('content')
<div class="grid gap-5 md:grid-cols-[1fr_1fr]">

    <div class="card">
        <div class="section-kicker">Thông tin yêu cầu #{{ $groupRequest->id }}</div>
        <div class="info-list mt-3">
            <div class="info-item"><span class="label">Trạng thái</span>
                @php
                    $statusBadge = ['new' => 'badge-orange', 'contacted' => 'badge-green', 'converted' => 'badge-blue'][$groupRequest->status] ?? 'badge-green';
                    $statusLabel = ['new' => 'Mới', 'contacted' => 'Đã liên hệ', 'converted' => 'Đã tạo đơn'][$groupRequest->status] ?? $groupRequest->status;
                @endphp
                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
            </div>
            <div class="info-item"><span class="label">Người liên hệ</span><span class="value">{{ $groupRequest->contact_name }}</span></div>
            @if ($groupRequest->company_name)
                <div class="info-item"><span class="label">Công ty</span><span class="value">{{ $groupRequest->company_name }}</span></div>
            @endif
            <div class="info-item"><span class="label">Email</span><span class="value">{{ $groupRequest->email }}</span></div>
            @if ($groupRequest->phone)
                <div class="info-item"><span class="label">Điện thoại</span><span class="value">{{ $groupRequest->phone }}</span></div>
            @endif
            <div class="info-item"><span class="label">Số khách</span><span class="value">{{ $groupRequest->group_size }} người</span></div>
            @if ($groupRequest->num_children)
                <div class="info-item"><span class="label">Trẻ em (6-11 tuổi)</span><span class="value">{{ $groupRequest->num_children }} trẻ</span></div>
            @endif
            @if ($groupRequest->num_infants)
                <div class="info-item"><span class="label">Trẻ sơ sinh (0-5 tuổi)</span><span class="value">{{ $groupRequest->num_infants }} trẻ</span></div>
            @endif
            @if ($groupRequest->room_count)
                <div class="info-item"><span class="label">Số phòng</span><span class="value">{{ $groupRequest->room_count }} phòng</span></div>
            @endif
            @if ($groupRequest->check_in && $groupRequest->check_out)
                <div class="info-item"><span class="label">Ngày dự kiến</span>
                    <span class="value">{{ $groupRequest->check_in->format('d/m/Y') }} → {{ $groupRequest->check_out->format('d/m/Y') }}</span>
                </div>
            @endif
            @if ($groupRequest->room_type_ids)
                <div class="info-item"><span class="label">Loại phòng quan tâm</span>
                    <span class="value">{{ $roomTypes->whereIn('id', $groupRequest->room_type_ids)->pluck('name')->implode(', ') ?: '—' }}</span>
                </div>
            @endif
            @if ($groupRequest->message)
                <div class="info-item flex-col items-start gap-1">
                    <span class="label">Ghi chú</span>
                    <span class="value text-left">{{ $groupRequest->message }}</span>
                </div>
            @endif
        </div>

        @if ($groupRequest->selected_suggestion)
            @php $sg = $groupRequest->selected_suggestion; @endphp
            <div class="alert alert-warning mt-3">
                <div class="font-semibold">
                    Khách quan tâm: {{ $sg['label'] ?? '' }} —
                    {{ collect($sg['rooms'] ?? [])->map(fn ($r) => ($r['quantity'] ?? '?').' phòng '.($r['room_type'] ?? '?'))->implode(', ') }}
                    (giá tạm tính: {{ number_format($sg['estimated_total_price'] ?? 0, 0, ',', '.') }}đ)
                </div>
                <p class="mt-1 text-xs">Đây là gợi ý tại thời điểm khách gửi yêu cầu, không phải phòng đã giữ. Vui lòng kiểm tra lại phòng trống trước khi gửi báo giá.</p>
            </div>
        @endif

        <div class="action-row mt-4">
            @if ($groupRequest->status === 'new')
                <form method="POST" action="{{ route('staff.group-bookings.mark-contacted', $groupRequest->id) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-outline btn-sm">Đánh dấu đã liên hệ</button>
                </form>
            @endif
            @if ($chatUrl)
                <a href="{{ $chatUrl }}" class="btn btn-outline btn-sm">💬 Xem chat khách</a>
            @else
                <span class="text-xs text-slate-400">Khách chưa có tài khoản — không thể chat</span>
            @endif
            <a href="{{ route('staff.group-bookings.index') }}" class="btn btn-outline btn-sm">← Quay lại</a>
        </div>
    </div>

    <div class="card">
        <div class="section-kicker">Tạo đơn đặt phòng</div>
        @if ($groupRequest->status === 'converted')
            <div class="alert alert-success">Yêu cầu này đã được chuyển thành đơn đặt phòng — không thể tạo thêm đơn từ yêu cầu này.</div>
        @else
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Điền thông tin bên dưới để tạo đơn đặt phòng thủ công cho đoàn này.</p>

        <form method="POST" action="{{ route('staff.group-bookings.create-booking', $groupRequest->id) }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Ngày nhận phòng *</label>
                    <input class="input" type="date" name="check_in"
                        value="{{ old('check_in', $groupRequest->check_in?->format('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="form-label">Ngày trả phòng *</label>
                    <input class="input" type="date" name="check_out"
                        value="{{ old('check_out', $groupRequest->check_out?->format('Y-m-d')) }}" required>
                </div>
            </div>

            <div>
                <label class="form-label">Loại phòng & số lượng *</label>
                <p class="mb-1 text-xs text-slate-500 dark:text-slate-400">NL: người lớn (≥12 tuổi) · TE: trẻ em (6-11 tuổi) · SS: sơ sinh (0-5 tuổi, miễn phí, tối đa 2/phòng)</p>
                <div id="items-container" class="space-y-2">
                    @foreach ($prefillItems as $i => $row)
                        <div class="item-row flex flex-col gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-bold whitespace-nowrap">Loại phòng</label>
                                <select name="items[{{ $i }}][room_type_id]" class="input" required onchange="toggleGroupExtraBed(this)">
                                    <option value="">-- Chọn loại phòng --</option>
                                    @foreach ($allRoomTypes as $rt)
                                        <option value="{{ $rt->id }}" data-supports-extra-bed="{{ $rt->supportsExtraBed() ? '1' : '0' }}" @selected((string)($row['room_type_id'] ?? '') === (string)$rt->id)>
                                            {{ $rt->name }} — {{ number_format($rt->price_per_night, 0, ',', '.') }}đ/đêm
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-wrap items-end gap-2">
                                <div class="flex w-20 flex-col gap-1">
                                    <label class="text-xs font-bold whitespace-nowrap">Số phòng</label>
                                    <input type="number" name="items[{{ $i }}][quantity]" class="input" min="1" value="{{ $row['quantity'] ?? 1 }}" required>
                                </div>
                                <div class="flex w-20 flex-col gap-1">
                                    <label class="text-xs font-bold whitespace-nowrap" title="Người lớn (≥12 tuổi)">NL</label>
                                    <input type="number" name="items[{{ $i }}][adults]" class="input" min="1" value="{{ $row['adults'] ?? 2 }}" title="Người lớn (≥12 tuổi)" required>
                                </div>
                                <div class="flex w-20 flex-col gap-1">
                                    <label class="text-xs font-bold whitespace-nowrap" title="Trẻ em 6-11 tuổi (tối đa 2/phòng)">TE</label>
                                    <input type="number" name="items[{{ $i }}][children]" class="input" min="0" value="{{ $row['children'] ?? 0 }}" title="Trẻ em 6-11 tuổi (tối đa 2/phòng)">
                                </div>
                                <div class="flex w-20 flex-col gap-1">
                                    <label class="text-xs font-bold whitespace-nowrap" title="Sơ sinh 0-5 tuổi (miễn phí, tối đa 2/phòng)">SS</label>
                                    <input type="number" name="items[{{ $i }}][infants]" class="input" min="0" value="{{ $row['infants'] ?? 0 }}" title="Sơ sinh 0-5 tuổi (miễn phí, tối đa 2/phòng)">
                                </div>
                                <label class="item-extra-bed-wrap hidden items-center gap-1.5 text-xs font-bold">
                                    <input type="checkbox" name="items[{{ $i }}][extra_bed]" class="item-extra-bed" value="1" @checked(! empty($row['extra_bed']))>
                                    Giường phụ?
                                </label>
                                <button type="button" onclick="this.closest('.item-row').remove()" class="btn btn-danger btn-sm ml-auto">✕</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" onclick="addRow()" class="btn btn-outline btn-sm mt-2">➕ Thêm loại phòng</button>
            </div>

            <div>
                <label class="form-label">Họ tên khách *</label>
                <input class="input" type="text" name="customer_name" value="{{ old('customer_name', $groupRequest->contact_name) }}" required>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Số điện thoại *</label>
                    <input class="input" type="text" name="customer_phone" value="{{ old('customer_phone', $groupRequest->phone) }}" required>
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input class="input" type="email" name="customer_email" value="{{ old('customer_email', $groupRequest->email) }}">
                </div>
            </div>
            <div>
                <label class="form-label">Ghi chú</label>
                <textarea class="input" name="note" rows="2">{{ old('note', $groupRequest->message) }}</textarea>
            </div>

            <button type="submit" class="btn-primary w-full">Tạo đơn đặt phòng</button>
        </form>
        @endif
    </div>
</div>

<template id="row-tpl">
    <div class="item-row flex flex-col gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-bold whitespace-nowrap">Loại phòng</label>
            <select name="items[__I__][room_type_id]" class="input" required onchange="toggleGroupExtraBed(this)">
                <option value="">-- Chọn loại phòng --</option>
                @foreach ($allRoomTypes as $rt)
                    <option value="{{ $rt->id }}" data-supports-extra-bed="{{ $rt->supportsExtraBed() ? '1' : '0' }}">{{ $rt->name }} — {{ number_format($rt->price_per_night, 0, ',', '.') }}đ/đêm</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-wrap items-end gap-2">
            <div class="flex w-20 flex-col gap-1">
                <label class="text-xs font-bold whitespace-nowrap">Số phòng</label>
                <input type="number" name="items[__I__][quantity]" class="input" min="1" value="1" required>
            </div>
            <div class="flex w-20 flex-col gap-1">
                <label class="text-xs font-bold whitespace-nowrap" title="Người lớn (≥12 tuổi)">NL</label>
                <input type="number" name="items[__I__][adults]" class="input" min="1" value="2" title="Người lớn (≥12 tuổi)" required>
            </div>
            <div class="flex w-20 flex-col gap-1">
                <label class="text-xs font-bold whitespace-nowrap" title="Trẻ em 6-11 tuổi (tối đa 2/phòng)">TE</label>
                <input type="number" name="items[__I__][children]" class="input" min="0" value="0" title="Trẻ em 6-11 tuổi (tối đa 2/phòng)">
            </div>
            <div class="flex w-20 flex-col gap-1">
                <label class="text-xs font-bold whitespace-nowrap" title="Sơ sinh 0-5 tuổi (miễn phí, tối đa 2/phòng)">SS</label>
                <input type="number" name="items[__I__][infants]" class="input" min="0" value="0" title="Sơ sinh 0-5 tuổi (miễn phí, tối đa 2/phòng)">
            </div>
            <label class="item-extra-bed-wrap hidden items-center gap-1.5 text-xs font-bold">
                <input type="checkbox" name="items[__I__][extra_bed]" class="item-extra-bed" value="1">
                Giường phụ?
            </label>
            <button type="button" onclick="this.closest('.item-row').remove()" class="btn btn-danger btn-sm ml-auto">✕</button>
        </div>
    </div>
</template>

<script>
let idx = {{ count($prefillItems) }};
function addRow() {
    const tpl = document.getElementById('row-tpl').innerHTML.replace(/__I__/g, idx++);
    document.getElementById('items-container').insertAdjacentHTML('beforeend', tpl);
}

// Chỉ loại phòng có RoomType::supportsExtraBed() = true mới hiện checkbox
// giường phụ — ẩn + bỏ tick nếu đổi sang loại phòng không hỗ trợ, tránh gửi
// extra_bed=1 "mồ côi" (khớp cách customer/booking/create.blade.php đang làm).
function toggleGroupExtraBed(select) {
    const row = select.closest('.item-row');
    const wrap = row.querySelector('.item-extra-bed-wrap');
    const checkbox = row.querySelector('.item-extra-bed');
    const opt = select.options[select.selectedIndex];
    const supportsExtraBed = opt && opt.dataset.supportsExtraBed === '1';

    if (supportsExtraBed) {
        wrap.classList.remove('hidden');
        wrap.classList.add('flex');
    } else {
        wrap.classList.add('hidden');
        wrap.classList.remove('flex');
        if (checkbox) checkbox.checked = false;
    }
}

document.querySelectorAll('#items-container select[name*="room_type_id"]').forEach(toggleGroupExtraBed);
</script>

<div class="card mt-5">
    <div class="section-kicker">Gửi báo giá qua chat</div>
    <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Tin nhắn sẽ gửi đến hộp chat của <strong>{{ $groupRequest->user?->name ?? $groupRequest->email }}</strong> kèm bảng giá sơ bộ và link đặt phòng.</p>

    <form method="POST" action="{{ route('staff.group-bookings.send-quote', $groupRequest->id) }}" class="space-y-4">
        @csrf

        <div>
            <label class="form-label">Các loại phòng báo giá *</label>
            <div id="quote-items-container" class="space-y-2">
                @foreach ($prefillItems as $qi => $row)
                    <div class="item-row flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <div class="flex min-w-[200px] flex-1 flex-col gap-1">
                            <label class="text-xs font-bold whitespace-nowrap">Loại phòng</label>
                            <select name="quote_items[{{ $qi }}][room_type_id]" class="input" required onchange="syncPrice(this); updateExtraBedWarning();">
                                <option value="">-- Chọn loại phòng --</option>
                                @foreach ($allRoomTypes as $rt)
                                    <option value="{{ $rt->id }}" data-price="{{ $rt->price_per_night }}" data-supports-extra-bed="{{ $rt->supportsExtraBed() ? '1' : '0' }}" @selected((string)($row['room_type_id'] ?? '') === (string)$rt->id)>
                                        {{ $rt->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex w-20 flex-col gap-1">
                            <label class="text-xs font-bold whitespace-nowrap">Số phòng</label>
                            <input type="number" name="quote_items[{{ $qi }}][quantity]" class="input" min="1" value="{{ $row['quantity'] ?? 1 }}" required>
                        </div>
                        <div class="flex w-36 flex-col gap-1">
                            <label class="text-xs font-bold whitespace-nowrap">Giá/đêm</label>
                            <input type="number" name="quote_items[{{ $qi }}][price_per_night]" class="input" min="0" step="1000"
                                value="{{ $allRoomTypes->firstWhere('id', $row['room_type_id'] ?? null)?->price_per_night ?? '' }}"
                                required>
                        </div>
                        <button type="button" onclick="this.closest('.item-row').remove(); updateExtraBedWarning();" class="btn btn-danger btn-sm">✕</button>
                    </div>
                @endforeach
            </div>
            <button type="button" onclick="addQuoteRow()" class="btn btn-outline btn-sm mt-2">➕ Thêm loại phòng</button>
        </div>

        @if ($groupRequest->num_children)
            <div>
                <label class="form-label">Giường phụ trẻ em (6-11 tuổi)</label>
                <p class="mb-1 text-xs text-slate-500 dark:text-slate-400">Yêu cầu khai {{ $groupRequest->num_children }} trẻ em 6-11 tuổi — mọi loại phòng hiện đều hỗ trợ giường phụ, tự kiểm tra loại phòng đã chọn ở trên trước khi báo giá.</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold whitespace-nowrap" for="extra_beds">Số giường phụ</label>
                        <input type="number" id="extra_beds" name="extra_beds" class="input" min="0"
                            value="{{ old('extra_beds', $groupRequest->num_children) }}" oninput="updateExtraBedWarning()">
                    </div>
                    <div>
                        <label class="text-xs font-bold whitespace-nowrap" for="extra_bed_price_per_night">Giá/giường/đêm</label>
                        <input type="number" id="extra_bed_price_per_night" name="extra_bed_price_per_night" class="input" min="0" step="1000"
                            value="{{ old('extra_bed_price_per_night', $extraBedSurchargePerNight) }}">
                    </div>
                </div>
                <p id="extra-bed-warning" class="mt-1 hidden text-xs text-amber-600 dark:text-amber-400">
                    Chưa chọn loại phòng nào hoặc loại phòng đã chọn không hỗ trợ giường phụ — báo giá sẽ vẫn cộng phụ thu này, vui lòng kiểm tra lại trước khi gửi.
                </p>
            </div>
        @endif

        <div>
            <label class="form-label">Ghi chú thêm (hiển thị trong email)</label>
            <textarea class="input" name="note" rows="3" placeholder="VD: Giá trên chưa bao gồm bữa sáng, liên hệ để được tư vấn thêm...">{{ old('note') }}</textarea>
        </div>

        <button type="submit" class="btn-primary w-full">Gửi báo giá</button>
    </form>
</div>

<template id="quote-row-tpl">
    <div class="item-row flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
        <div class="flex min-w-[200px] flex-1 flex-col gap-1">
            <label class="text-xs font-bold whitespace-nowrap">Loại phòng</label>
            <select name="quote_items[__I__][room_type_id]" class="input" required onchange="syncPrice(this); updateExtraBedWarning();">
                <option value="">-- Chọn loại phòng --</option>
                @foreach ($allRoomTypes as $rt)
                    <option value="{{ $rt->id }}" data-price="{{ $rt->price_per_night }}" data-supports-extra-bed="{{ $rt->supportsExtraBed() ? '1' : '0' }}">{{ $rt->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex w-20 flex-col gap-1">
            <label class="text-xs font-bold whitespace-nowrap">Số phòng</label>
            <input type="number" name="quote_items[__I__][quantity]" class="input" min="1" value="1" required>
        </div>
        <div class="flex w-36 flex-col gap-1">
            <label class="text-xs font-bold whitespace-nowrap">Giá/đêm</label>
            <input type="number" name="quote_items[__I__][price_per_night]" class="input" min="0" step="1000" required>
        </div>
        <button type="button" onclick="this.closest('.item-row').remove(); updateExtraBedWarning();" class="btn btn-danger btn-sm">✕</button>
    </div>
</template>

<script>
let qIdx = {{ count($prefillItems) }};
function addQuoteRow() {
    const tpl = document.getElementById('quote-row-tpl').innerHTML.replace(/__I__/g, qIdx++);
    document.getElementById('quote-items-container').insertAdjacentHTML('beforeend', tpl);
    updateExtraBedWarning();
}
function syncPrice(select) {
    const price = select.options[select.selectedIndex]?.dataset.price;
    if (price) select.closest('.item-row').querySelector('[name*=price_per_night]').value = price;
}
document.querySelectorAll('#quote-items-container select').forEach(s => s.addEventListener('change', () => syncPrice(s)));

// Chỉ cảnh báo tham khảo — không chặn submit. Nhân viên tự nhập số giường
// phụ (không tự động suy ra từ num_children, xem ghi chú trong Controller),
// nên cần nhắc rõ nếu TẤT CẢ loại phòng đã chọn trong báo giá đều không hỗ
// trợ giường phụ thật (RoomType::supportsExtraBed() — đọc động qua
// data-supports-extra-bed trên từng option, không hard-code tên category cụ
// thể ở đây để luôn khớp danh sách category thật dù sau này có đổi).
function updateExtraBedWarning() {
    const extraBedsInput = document.getElementById('extra_beds');
    const warningEl       = document.getElementById('extra-bed-warning');
    if (! extraBedsInput || ! warningEl) return;

    const extraBeds = parseInt(extraBedsInput.value, 10) || 0;

    const selects = document.querySelectorAll('#quote-items-container select[name*="room_type_id"]');
    const anySupports = Array.from(selects).some((s) => {
        const opt = s.options[s.selectedIndex];
        return opt && opt.value !== '' && opt.dataset.supportsExtraBed === '1';
    });

    if (extraBeds > 0 && selects.length > 0 && ! anySupports) {
        warningEl.classList.remove('hidden');
    } else {
        warningEl.classList.add('hidden');
    }
}

updateExtraBedWarning();
</script>
@endsection
