@extends('layouts.staff')

@section('title', 'Yêu cầu trả phòng muộn #' . $lateCheckoutRequest->id . ' · Homi Staff')
@section('page_title', 'Yêu cầu trả phòng muộn #' . $lateCheckoutRequest->id)
@section('page_subtitle', 'Đơn ' . $lateCheckoutRequest->booking->booking_code)

@section('content')

@php
    $statusBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$lateCheckoutRequest->status] ?? 'badge-green';
    $statusLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$lateCheckoutRequest->status] ?? $lateCheckoutRequest->status;
@endphp

<div class="card">
    <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>

    <div class="info-list mt-4">
        <div class="info-item">
            <span class="label">Đơn</span>
            <span class="value"><a href="{{ route('staff.bookings.show', $lateCheckoutRequest->booking_id) }}">{{ $lateCheckoutRequest->booking->booking_code }}</a></span>
        </div>
        <div class="info-item">
            <span class="label">Khách</span>
            <span class="value">{{ $lateCheckoutRequest->user->name }} ({{ $lateCheckoutRequest->user->email }})</span>
        </div>
        <div class="info-item">
            <span class="label">Ngày trả phòng</span>
            <span class="value">{{ $lateCheckoutRequest->booking->check_out->format('d/m/Y') }}</span>
        </div>
        <div class="info-item">
            <span class="label">Giờ yêu cầu</span>
            <span class="value">{{ substr($lateCheckoutRequest->requested_checkout_time, 0, 5) }}</span>
        </div>
        <div class="info-item">
            <span class="label">Số giờ trễ</span>
            <span class="value">{{ $lateCheckoutRequest->hours_late }} giờ</span>
        </div>
        <div class="info-item">
            <span class="label">Phụ phí</span>
            <span class="value">{{ number_format($lateCheckoutRequest->fee_amount, 0, ',', '.') }}đ</span>
        </div>
        <div class="info-item">
            <span class="label">Lý do</span>
            <span class="value">{{ $lateCheckoutRequest->reason ?? '—' }}</span>
        </div>
        @if ($lateCheckoutRequest->status !== 'pending')
            <div class="info-item">
                <span class="label">Xử lý bởi</span>
                <span class="value">{{ $lateCheckoutRequest->handledByUser?->name ?? 'Hệ thống (tự động duyệt)' }} lúc {{ $lateCheckoutRequest->handled_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
            </div>
            @if ($lateCheckoutRequest->staff_note)
                <div class="info-item">
                    <span class="label">Ghi chú</span>
                    <span class="value">{{ $lateCheckoutRequest->staff_note }}</span>
                </div>
            @endif
        @endif
    </div>

    @if ($lateCheckoutRequest->status === 'pending')
        <div class="quick-actions-row mt-5">
            <form method="POST" action="{{ route('staff.late-checkout-requests.approve', $lateCheckoutRequest->id) }}"
                onsubmit="return confirm('Duyệt yêu cầu trả phòng muộn này? Hệ thống sẽ tự cộng phụ phí {{ number_format($lateCheckoutRequest->fee_amount, 0, ',', '.') }}đ vào đơn. Hãy chắc chắn đã kiểm tra phòng không cần dọn gấp cho khách kế tiếp.');">
                @csrf
                <button type="submit" class="btn btn-primary">Duyệt yêu cầu</button>
            </form>

            <form method="POST" action="{{ route('staff.late-checkout-requests.reject', $lateCheckoutRequest->id) }}" class="flex items-center gap-2">
                @csrf
                <input type="text" name="staff_note" placeholder="Lý do từ chối (tuỳ chọn)" class="input">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Từ chối yêu cầu trả phòng muộn này?');">Từ chối</button>
            </form>
        </div>
    @endif
</div>
@endsection
