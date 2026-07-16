@extends('layouts.admin')

@section('title', 'Chi tiết yêu cầu đoàn · Homi Admin')
@section('page_title', 'Yêu cầu đặt đoàn/nhóm')
@section('page_subtitle', 'Xem thông tin và tạo đơn đặt phòng thủ công từ yêu cầu này.')

@section('content')
@php
    // Tính trước ở top-level (không nằm trong form) để script cuối trang
    // (đếm số dòng item ban đầu) luôn có $prefillItems, kể cả khi form tạo
    // đơn bị ẩn do yêu cầu đã converted.
    $prefillItems = old('items', $groupRequest->room_type_ids
        ? array_map(fn($id) => ['room_type_id' => $id, 'quantity' => 1, 'adults' => 2, 'children' => 0], $groupRequest->room_type_ids)
        : [['room_type_id' => '', 'quantity' => 1, 'adults' => 2, 'children' => 0]]);
@endphp
<div class="grid gap-5 md:grid-cols-[1fr_1fr]">

    {{-- Thông tin yêu cầu --}}
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

        <div class="action-row mt-4">
            @if ($groupRequest->status === 'new')
                <form method="POST" action="{{ route('admin.group-bookings.mark-contacted', $groupRequest->id) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-outline btn-sm">Đánh dấu đã liên hệ</button>
                </form>
            @endif
            <a href="{{ route('admin.group-bookings.index') }}" class="btn btn-outline btn-sm">← Quay lại</a>
        </div>
    </div>

    {{-- Form tạo đơn thủ công --}}
    <div class="card">
        <div class="section-kicker">Tạo đơn đặt phòng</div>
        @if ($groupRequest->status === 'converted')
            <div class="alert alert-success">Yêu cầu này đã được chuyển thành đơn đặt phòng — không thể tạo thêm đơn từ yêu cầu này.</div>
        @else
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Điền thông tin bên dưới để tạo đơn đặt phòng thủ công cho đoàn này.</p>

        <form method="POST" action="{{ route('admin.group-bookings.create-booking', $groupRequest->id) }}" class="space-y-4">
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
                <div id="items-container" class="space-y-2">
                    @foreach ($prefillItems as $i => $row)
                        <div class="item-row flex flex-wrap gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                            <select name="items[{{ $i }}][room_type_id]" class="input flex-1" required>
                                <option value="">-- Chọn loại phòng --</option>
                                @foreach ($allRoomTypes as $rt)
                                    <option value="{{ $rt->id }}" @selected((string)($row['room_type_id'] ?? '') === (string)$rt->id)>
                                        {{ $rt->name }} — {{ number_format($rt->price_per_night, 0, ',', '.') }}đ/đêm
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="items[{{ $i }}][quantity]" class="input w-20" min="1" value="{{ $row['quantity'] ?? 1 }}" placeholder="Phòng" required>
                            <input type="number" name="items[{{ $i }}][adults]" class="input w-20" min="1" value="{{ $row['adults'] ?? 2 }}" placeholder="NL" required>
                            <input type="number" name="items[{{ $i }}][children]" class="input w-20" min="0" value="{{ $row['children'] ?? 0 }}" placeholder="TE">
                            <button type="button" onclick="this.closest('.item-row').remove()" class="btn btn-danger btn-sm">✕</button>
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
    <div class="item-row flex flex-wrap gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
        <select name="items[__I__][room_type_id]" class="input flex-1" required>
            <option value="">-- Chọn loại phòng --</option>
            @foreach ($allRoomTypes as $rt)
                <option value="{{ $rt->id }}">{{ $rt->name }} — {{ number_format($rt->price_per_night, 0, ',', '.') }}đ/đêm</option>
            @endforeach
        </select>
        <input type="number" name="items[__I__][quantity]" class="input w-20" min="1" value="1" placeholder="Phòng" required>
        <input type="number" name="items[__I__][adults]" class="input w-20" min="1" value="2" placeholder="NL" required>
        <input type="number" name="items[__I__][children]" class="input w-20" min="0" value="0" placeholder="TE">
        <button type="button" onclick="this.closest('.item-row').remove()" class="btn btn-danger btn-sm">✕</button>
    </div>
</template>

<script>
let idx = {{ count($prefillItems) }};
function addRow() {
    const tpl = document.getElementById('row-tpl').innerHTML.replace(/__I__/g, idx++);
    document.getElementById('items-container').insertAdjacentHTML('beforeend', tpl);
}
</script>
@endsection
