@extends('layouts.staff')

@section('title', 'Đề xuất giảm giá đoàn #' . $groupDiscountRequest->id . ' · Homi Nhân viên')
@section('page_title', 'Đề xuất giảm giá đoàn #' . $groupDiscountRequest->id)
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
            <span class="value"><a href="{{ route('staff.bookings.show', $groupDiscountRequest->booking_id) }}">{{ $groupDiscountRequest->booking->booking_code }}</a></span>
        </div>
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
        <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">Đề xuất đang chờ admin duyệt — bạn sẽ nhận thông báo khi có kết quả.</p>
    @endif
</div>
@endsection
