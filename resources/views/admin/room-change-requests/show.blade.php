@extends('layouts.admin')

@section('title', 'Yêu cầu đổi phòng #' . $roomChangeRequest->id . ' · Homi Admin')
@section('page_title', 'Yêu cầu đổi phòng #' . $roomChangeRequest->id)
@section('page_subtitle', 'Đơn ' . $roomChangeRequest->booking->booking_code)

@section('content')

@php
    $statusBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$roomChangeRequest->status] ?? 'badge-green';
    $statusLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$roomChangeRequest->status] ?? $roomChangeRequest->status;
    $item = $roomChangeRequest->bookingItem ?? $roomChangeRequest->booking->bookingItems->first();
@endphp

<div class="card">
    <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>

    <div class="info-list mt-4">
        <div class="info-item">
            <span class="label">Đơn</span>
            <span class="value"><a href="{{ route('admin.bookings.show', $roomChangeRequest->booking_id) }}">{{ $roomChangeRequest->booking->booking_code }}</a></span>
        </div>
        <div class="info-item">
            <span class="label">Khách</span>
            <span class="value">{{ $roomChangeRequest->user->name }} ({{ $roomChangeRequest->user->email }})</span>
        </div>
        @if ($roomChangeRequest->booking->bookingItems->count() > 1)
            <div class="info-item">
                <span class="label">Loại đơn</span>
                <span class="value">Đặt đoàn (nhiều loại phòng) — yêu cầu đổi đúng dòng "{{ $item?->roomType?->name ?? '—' }}"</span>
            </div>
        @endif
        <div class="info-item">
            <span class="label">Loại phòng hiện tại</span>
            <span class="value">{{ $roomChangeRequest->currentRoomType->name ?? '—' }}</span>
        </div>
        <div class="info-item">
            <span class="label">Loại phòng yêu cầu</span>
            <span class="value">{{ $roomChangeRequest->requestedRoomType->name ?? 'Giữ nguyên' }}</span>
        </div>
        <div class="info-item">
            <span class="label">Ngày ở hiện tại</span>
            <span class="value">{{ $roomChangeRequest->current_check_in?->format('d/m/Y') }} - {{ $roomChangeRequest->current_check_out?->format('d/m/Y') }}</span>
        </div>
        <div class="info-item">
            <span class="label">Ngày ở yêu cầu</span>
            <span class="value">
                @if ($roomChangeRequest->requested_check_in && $roomChangeRequest->requested_check_out)
                    {{ $roomChangeRequest->requested_check_in->format('d/m/Y') }} - {{ $roomChangeRequest->requested_check_out->format('d/m/Y') }}
                @else
                    Giữ nguyên
                @endif
            </span>
        </div>
        <div class="info-item">
            <span class="label">Số lượng phòng / khách</span>
            <span class="value">{{ $item?->quantity }} phòng, {{ $item?->adults }} người lớn + {{ $item?->children }} trẻ em</span>
        </div>
        @if ($roomChangeRequest->requestedRoomType && $roomChangeRequest->quantity && $item && $roomChangeRequest->quantity < $item->quantity)
            <div class="info-item">
                <span class="label">Số phòng muốn đổi</span>
                <span class="value">{{ $roomChangeRequest->quantity }} / {{ $item->quantity }} phòng — chỉ đổi 1 phần, {{ $item->quantity - $roomChangeRequest->quantity }} phòng còn lại giữ nguyên loại "{{ $roomChangeRequest->currentRoomType->name ?? '—' }}"</span>
            </div>
        @endif
        <div class="info-item">
            <span class="label">Lý do</span>
            <span class="value">{{ $roomChangeRequest->reason ?? '—' }}</span>
        </div>
        @if ($roomChangeRequest->status !== 'pending')
            <div class="info-item">
                <span class="label">Xử lý bởi</span>
                <span class="value">{{ $roomChangeRequest->handledByUser->name ?? '—' }} lúc {{ $roomChangeRequest->handled_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
            </div>
            @if ($roomChangeRequest->staff_note)
                <div class="info-item">
                    <span class="label">Ghi chú</span>
                    <span class="value">{{ $roomChangeRequest->staff_note }}</span>
                </div>
            @endif
        @endif
    </div>

    @if ($roomChangeRequest->status === 'pending')
        <div class="quick-actions-row mt-5">
            <form method="POST" action="{{ route('admin.room-change-requests.approve', $roomChangeRequest->id) }}"
                onsubmit="return confirm('Duyệt yêu cầu đổi phòng này? Hệ thống sẽ tự cập nhật đơn và tính lại giá.');">
                @csrf
                <button type="submit" class="btn btn-primary">Duyệt yêu cầu</button>
            </form>

            <form method="POST" action="{{ route('admin.room-change-requests.reject', $roomChangeRequest->id) }}" class="flex items-center gap-2">
                @csrf
                <input type="text" name="staff_note" placeholder="Lý do từ chối (tuỳ chọn)" class="input">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Từ chối yêu cầu đổi phòng này?');">Từ chối</button>
            </form>
        </div>
    @endif
</div>
@endsection
