@extends('layouts.admin')

@section('title', 'Ưu đãi đoàn #' . $groupDiscountRequest->id . ' · Homi Admin')
@section('page_title', 'Ưu đãi đoàn #' . $groupDiscountRequest->id)
@section('page_subtitle', 'Đơn ' . $groupDiscountRequest->booking->booking_code)

@section('content')

@php
    $statusBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$groupDiscountRequest->status] ?? 'badge-green';
    $statusLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$groupDiscountRequest->status] ?? $groupDiscountRequest->status;
@endphp

<div class="card">
    <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
    <span class="badge {{ $groupDiscountRequest->type === 'policy_tier' ? 'badge-blue' : 'badge-orange' }}">
        {{ $groupDiscountRequest->type === 'policy_tier' ? 'Tự động theo chính sách' : 'Đề xuất/áp dụng thêm' }}
    </span>

    <div class="info-list mt-4">
        <div class="info-item">
            <span class="label">Đơn</span>
            <span class="value"><a href="{{ route('admin.bookings.show', $groupDiscountRequest->booking_id) }}">{{ $groupDiscountRequest->booking->booking_code }}</a></span>
        </div>
        <div class="info-item">
            <span class="label">Nhân viên</span>
            <span class="value">{{ $groupDiscountRequest->user->name ?? '—' }} ({{ $groupDiscountRequest->user->email ?? '—' }})</span>
        </div>
        @if ($groupDiscountRequest->policy)
            <div class="info-item">
                <span class="label">Chính sách áp dụng</span>
                <span class="value">{{ $groupDiscountRequest->policy->name ?: ('≥' . $groupDiscountRequest->policy->min_rooms . ' phòng') }}</span>
            </div>
        @endif
        @if ($groupDiscountRequest->room_count)
            <div class="info-item">
                <span class="label">Số phòng</span>
                <span class="value">{{ $groupDiscountRequest->room_count }}</span>
            </div>
        @endif
        <div class="info-item">
            <span class="label">Tổng tiền gốc (trước giảm)</span>
            <span class="value">{{ number_format($groupDiscountRequest->original_subtotal, 0, ',', '.') }}đ</span>
        </div>
        <div class="info-item">
            <span class="label">Đề xuất</span>
            <span class="value">{{ (float) $groupDiscountRequest->requested_percent }}% (~{{ number_format($groupDiscountRequest->requested_amount, 0, ',', '.') }}đ)</span>
        </div>
        @if ($groupDiscountRequest->approved_percent !== null)
            <div class="info-item">
                <span class="label">Đã duyệt</span>
                <span class="value">{{ (float) $groupDiscountRequest->approved_percent }}% (~{{ number_format($groupDiscountRequest->approved_amount, 0, ',', '.') }}đ)</span>
            </div>
        @endif
        @if ($groupDiscountRequest->staff_cap_percent_snapshot !== null)
            <div class="info-item">
                <span class="label">Trần cấu hình lúc đề xuất</span>
                <span class="value">{{ (float) $groupDiscountRequest->staff_cap_percent_snapshot }}%</span>
            </div>
        @endif
        <div class="info-item">
            <span class="label">Lý do</span>
            <span class="value">{{ $groupDiscountRequest->reason ?? '—' }}</span>
        </div>
        @if ($groupDiscountRequest->status !== 'pending')
            <div class="info-item">
                <span class="label">Xử lý bởi</span>
                <span class="value">{{ $groupDiscountRequest->handledByUser->name ?? 'Hệ thống (tự động theo chính sách)' }} lúc {{ $groupDiscountRequest->handled_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
            </div>
            @if ($groupDiscountRequest->admin_note)
                <div class="info-item">
                    <span class="label">Ghi chú admin</span>
                    <span class="value">{{ $groupDiscountRequest->admin_note }}</span>
                </div>
            @endif
        @endif
    </div>

    @if ($groupDiscountRequest->status === 'pending')
        <div class="quick-actions-row mt-5" style="flex-wrap: wrap; gap: 12px;">
            <form method="POST" action="{{ route('admin.group-discount-requests.approve', $groupDiscountRequest->id) }}"
                onsubmit="return confirm('Duyệt đúng mức {{ (float) $groupDiscountRequest->requested_percent }}% đã đề xuất?');">
                @csrf
                <button type="submit" class="btn btn-primary">✅ Duyệt {{ (float) $groupDiscountRequest->requested_percent }}%</button>
            </form>

            <form method="POST" action="{{ route('admin.group-discount-requests.adjust', $groupDiscountRequest->id) }}" style="display:flex; gap:8px; align-items:center;">
                @csrf
                <input type="number" name="percent" min="0.01" max="100" step="0.1" class="input" style="width:100px;" placeholder="VD 7" value="{{ old('percent') }}" required>
                <span>%</span>
                <input type="text" name="admin_note" placeholder="Ghi chú (tuỳ chọn)" class="input">
                <button type="submit" class="btn btn-outline" onclick="return confirm('Áp dụng mức % đã điều chỉnh thay vì mức đề xuất?');">✏️ Điều chỉnh &amp; duyệt</button>
            </form>

            <form method="POST" action="{{ route('admin.group-discount-requests.reject', $groupDiscountRequest->id) }}" style="display:flex; gap:8px; align-items:center;">
                @csrf
                <input type="text" name="admin_note" placeholder="Lý do từ chối (tuỳ chọn)" class="input">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Từ chối đề xuất giảm giá này?');">❌ Từ chối</button>
            </form>
        </div>
    @endif
</div>
@endsection
