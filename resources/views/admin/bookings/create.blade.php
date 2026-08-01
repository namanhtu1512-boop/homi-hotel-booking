@extends('layouts.admin')

@section('title', 'Đặt phòng tại quầy · Homi Admin')
@section('page_title', 'Đặt phòng tại quầy')
@section('page_subtitle', 'Tạo đơn đặt phòng trực tiếp cho khách vãng lai — đơn được xác nhận ngay, thanh toán tại khách sạn.')

@section('content')
@php
    $prefillItems = old('items', [['room_type_id' => '', 'quantity' => 1, 'adults' => 2, 'children' => 0, 'infants' => 0]]);
@endphp
<div class="card mx-auto max-w-2xl">
    <form method="POST" action="{{ route('admin.bookings.store') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="form-label">Ngày nhận phòng *</label>
                <input class="input" type="date" name="check_in" value="{{ old('check_in') }}" required>
            </div>
            <div>
                <label class="form-label">Ngày trả phòng *</label>
                <input class="input" type="date" name="check_out" value="{{ old('check_out') }}" required>
            </div>
        </div>

        <div>
            <label class="form-label">Loại phòng & số lượng *</label>
            <p class="mb-1 text-xs text-slate-500 dark:text-slate-400">NL: người lớn (≥12 tuổi) · TE: trẻ em (6-11 tuổi) · SS: sơ sinh (0-5 tuổi, miễn phí)</p>
            <div id="items-container" class="space-y-2">
                @foreach ($prefillItems as $i => $row)
                    <div class="item-row flex flex-wrap gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <select name="items[{{ $i }}][room_type_id]" class="input flex-1" required>
                            <option value="">-- Chọn loại phòng --</option>
                            @foreach ($roomTypes as $rt)
                                <option value="{{ $rt->id }}" @selected((string)($row['room_type_id'] ?? '') === (string)$rt->id)>
                                    {{ $rt->name }} — {{ number_format($rt->price_per_night, 0, ',', '.') }}đ/đêm
                                </option>
                            @endforeach
                        </select>
                        <input type="number" name="items[{{ $i }}][quantity]" class="input w-20" min="1" value="{{ $row['quantity'] ?? 1 }}" placeholder="Phòng" required>
                        <input type="number" name="items[{{ $i }}][adults]" class="input w-20" min="1" value="{{ $row['adults'] ?? 2 }}" placeholder="NL" title="Người lớn (≥12 tuổi)" required>
                        <input type="number" name="items[{{ $i }}][children]" class="input w-20" min="0" value="{{ $row['children'] ?? 0 }}" placeholder="TE" title="Trẻ em 6-11 tuổi (tối đa 2/phòng)">
                        <input type="number" name="items[{{ $i }}][infants]" class="input w-20" min="0" value="{{ $row['infants'] ?? 0 }}" placeholder="SS" title="Sơ sinh 0-5 tuổi (miễn phí)">
                        <button type="button" onclick="this.closest('.item-row').remove()" class="btn btn-danger btn-sm">✕</button>
                    </div>
                @endforeach
            </div>
            <button type="button" onclick="addRow()" class="btn btn-outline btn-sm mt-2">➕ Thêm loại phòng</button>
        </div>

        <div>
            <label class="form-label">Họ tên khách *</label>
            <input class="input" type="text" name="customer_name" value="{{ old('customer_name') }}" required>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="form-label">Số điện thoại *</label>
                <input class="input" type="text" name="customer_phone" value="{{ old('customer_phone') }}" required>
            </div>
            <div>
                <label class="form-label">Email</label>
                <input class="input" type="email" name="customer_email" value="{{ old('customer_email') }}">
            </div>
        </div>
        <div>
            <label class="form-label">Ghi chú</label>
            <textarea class="input" name="note" rows="2">{{ old('note') }}</textarea>
        </div>

        <div class="action-row">
            <button type="submit" class="btn-primary">Tạo đơn đặt phòng</button>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline">Hủy</a>
        </div>
    </form>
</div>

<template id="row-tpl">
    <div class="item-row flex flex-wrap gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
        <select name="items[__I__][room_type_id]" class="input flex-1" required>
            <option value="">-- Chọn loại phòng --</option>
            @foreach ($roomTypes as $rt)
                <option value="{{ $rt->id }}">{{ $rt->name }} — {{ number_format($rt->price_per_night, 0, ',', '.') }}đ/đêm</option>
            @endforeach
        </select>
        <input type="number" name="items[__I__][quantity]" class="input w-20" min="1" value="1" placeholder="Phòng" required>
        <input type="number" name="items[__I__][adults]" class="input w-20" min="1" value="2" placeholder="NL" title="Người lớn (≥12 tuổi)" required>
        <input type="number" name="items[__I__][children]" class="input w-20" min="0" value="0" placeholder="TE" title="Trẻ em 6-11 tuổi (tối đa 2/phòng)">
        <input type="number" name="items[__I__][infants]" class="input w-20" min="0" value="0" placeholder="SS" title="Sơ sinh 0-5 tuổi (miễn phí)">
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
