@extends('layouts.staff')

@section('title', 'Yêu cầu nhận phòng sớm #' . $earlyCheckinRequest->id . ' · Homi Staff')
@section('page_title', 'Yêu cầu nhận phòng sớm #' . $earlyCheckinRequest->id)
@section('page_subtitle', 'Đơn ' . $earlyCheckinRequest->booking->booking_code)

@section('content')

@php
    $statusBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$earlyCheckinRequest->status] ?? 'badge-green';
    $statusLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$earlyCheckinRequest->status] ?? $earlyCheckinRequest->status;
@endphp

<div class="card">
    <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>

    <div class="info-list mt-4">
        <div class="info-item">
            <span class="label">Đơn</span>
            <span class="value"><a href="{{ route('staff.bookings.show', $earlyCheckinRequest->booking_id) }}">{{ $earlyCheckinRequest->booking->booking_code }}</a></span>
        </div>
        <div class="info-item">
            <span class="label">Khách</span>
            <span class="value">{{ $earlyCheckinRequest->user->name }} ({{ $earlyCheckinRequest->user->email }})</span>
        </div>
        <div class="info-item">
            <span class="label">Ngày nhận phòng</span>
            <span class="value">{{ $earlyCheckinRequest->booking->check_in->format('d/m/Y') }}</span>
        </div>
        <div class="info-item">
            <span class="label">Giờ yêu cầu</span>
            <span class="value">{{ substr($earlyCheckinRequest->requested_arrival_time, 0, 5) }}</span>
        </div>
        <div class="info-item">
            <span class="label">Phòng nhận sớm</span>
            <span class="value">
                @foreach ($earlyCheckinRequest->selectedRoomLines() as $line)
                    <span class="badge badge-blue">{{ $line['quantity'] }} x {{ $line['item']->roomType->name ?? 'Phòng' }}</span>
                @endforeach
            </span>
        </div>
        <div class="info-item">
            <span class="label">Số giờ sớm</span>
            <span class="value">{{ $earlyCheckinRequest->hours_early }} giờ</span>
        </div>
        <div class="info-item">
            <span class="label">Phụ phí</span>
            <span class="value">{{ number_format($earlyCheckinRequest->fee_amount, 0, ',', '.') }}đ</span>
        </div>
        <div class="info-item">
            <span class="label">Lý do</span>
            <span class="value">{{ $earlyCheckinRequest->reason ?? '—' }}</span>
        </div>
        @if ($earlyCheckinRequest->status !== 'pending')
            <div class="info-item">
                <span class="label">Xử lý bởi</span>
                <span class="value">{{ $earlyCheckinRequest->handledByUser->name ?? '—' }} lúc {{ $earlyCheckinRequest->handled_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
            </div>
            @if ($earlyCheckinRequest->staff_note)
                <div class="info-item">
                    <span class="label">Ghi chú</span>
                    <span class="value">{{ $earlyCheckinRequest->staff_note }}</span>
                </div>
            @endif
        @endif
    </div>

    @if ($earlyCheckinRequest->status === 'pending')
        <div class="quick-actions-row mt-5">
            <form method="POST" action="{{ route('staff.early-checkin-requests.approve', $earlyCheckinRequest->id) }}"
                onsubmit="return confirm('Duyệt yêu cầu nhận phòng sớm này? Hệ thống sẽ tự cộng phụ phí {{ number_format($earlyCheckinRequest->fee_amount, 0, ',', '.') }}đ vào đơn.');">
                @csrf
                <button type="submit" class="btn btn-primary">Duyệt yêu cầu</button>
            </form>

            <form method="POST" action="{{ route('staff.early-checkin-requests.reject', $earlyCheckinRequest->id) }}" class="flex items-center gap-2">
                @csrf
                <input type="text" name="staff_note" placeholder="Lý do từ chối (tuỳ chọn)" class="input">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Từ chối yêu cầu nhận phòng sớm này?');">Từ chối</button>
            </form>
        </div>
    @endif
</div>
@endsection
