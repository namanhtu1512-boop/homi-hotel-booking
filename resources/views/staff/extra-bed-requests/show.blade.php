@extends('layouts.staff')

@section('title', 'Yêu cầu giường phụ #' . $extraBedRequest->id . ' · Homi Staff')
@section('page_title', 'Yêu cầu giường phụ #' . $extraBedRequest->id)
@section('page_subtitle', 'Đơn ' . $extraBedRequest->booking->booking_code)

@section('content')

@php
    $statusBadge = ['pending' => 'badge-orange', 'waitlisted' => 'badge-blue', 'resolved' => 'badge-green'][$extraBedRequest->status] ?? 'badge-green';
    $statusLabel = ['pending' => 'Chờ xử lý', 'waitlisted' => 'Waitlist', 'resolved' => 'Đã xử lý'][$extraBedRequest->status] ?? $extraBedRequest->status;
    $resolutionLabel = ['upgrade_room' => 'Đổi sang phòng lớn hơn', 'add_room' => 'Thêm phòng cùng loại', 'drop_extra_bed' => 'Bỏ yêu cầu giường phụ', 'waitlist' => 'Vào danh sách chờ', 'staff_cancel' => 'Nhân viên hủy đơn'][$extraBedRequest->resolution] ?? null;
    $item = $extraBedRequest->bookingItem;
@endphp

<div class="card">
    <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>

    <div class="info-list mt-4">
        <div class="info-item">
            <span class="label">Đơn</span>
            <span class="value"><a href="{{ route('staff.bookings.show', $extraBedRequest->booking_id) }}">{{ $extraBedRequest->booking->booking_code }}</a></span>
        </div>
        <div class="info-item">
            <span class="label">Khách</span>
            <span class="value">{{ $extraBedRequest->booking->customer_name }} ({{ $extraBedRequest->booking->customer_phone }})</span>
        </div>
        <div class="info-item">
            <span class="label">Loại phòng</span>
            <span class="value">{{ $item->roomType->name ?? '—' }} — {{ $item->quantity ?? '?' }} phòng, {{ $item->adults ?? '?' }} người lớn + {{ $item->children ?? 0 }} trẻ em</span>
        </div>
        <div class="info-item">
            <span class="label">Ngày ở</span>
            <span class="value">{{ $extraBedRequest->booking->check_in->format('d/m/Y') }} - {{ $extraBedRequest->booking->check_out->format('d/m/Y') }}</span>
        </div>
        <div class="info-item">
            <span class="label">Giường phụ</span>
            <span class="value">Cần {{ $extraBedRequest->requested_extra_beds }}, lúc kiểm tra chỉ còn {{ $extraBedRequest->available_extra_beds }}.</span>
        </div>
        @if ($extraBedRequest->status !== 'pending')
            <div class="info-item">
                <span class="label">Phương án</span>
                <span class="value">{{ $resolutionLabel ?? $extraBedRequest->resolution }}</span>
            </div>
            @if ($extraBedRequest->handledByUser)
                <div class="info-item">
                    <span class="label">Xử lý bởi</span>
                    <span class="value">{{ $extraBedRequest->handledByUser->name }} lúc {{ $extraBedRequest->handled_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
                </div>
            @else
                <div class="info-item">
                    <span class="label">Xử lý bởi</span>
                    <span class="value">Khách tự chọn</span>
                </div>
            @endif
        @endif
    </div>

    @if ($extraBedRequest->status === 'pending')
        <div class="mt-5">
            <span class="section-kicker">Chọn phương án xử lý</span>
            <div class="quick-actions-row mt-2">
                <form method="POST" action="{{ route('staff.extra-bed-requests.resolve', $extraBedRequest->id) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="choice" value="upgrade_room">
                    <select name="room_type_id" class="input" required>
                        <option value="">-- Chọn loại phòng lớn hơn --</option>
                        @foreach ($roomTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->capacity }} khách/phòng)</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Đổi phòng cho đơn này?');">Đổi phòng</button>
                </form>

                <form method="POST" action="{{ route('staff.extra-bed-requests.resolve', $extraBedRequest->id) }}">
                    @csrf
                    <input type="hidden" name="choice" value="add_room">
                    <button type="submit" class="btn btn-outline" onclick="return confirm('Đặt thêm 1 phòng cùng loại cho đơn này?');">Thêm phòng cùng loại</button>
                </form>

                <form method="POST" action="{{ route('staff.extra-bed-requests.resolve', $extraBedRequest->id) }}">
                    @csrf
                    <input type="hidden" name="choice" value="drop_extra_bed">
                    <button type="submit" class="btn btn-outline" onclick="return confirm('Xác nhận đơn KHÔNG cần giường phụ?');">Bỏ yêu cầu giường phụ</button>
                </form>

                <form method="POST" action="{{ route('staff.extra-bed-requests.resolve', $extraBedRequest->id) }}">
                    @csrf
                    <input type="hidden" name="choice" value="waitlist">
                    <button type="submit" class="btn btn-outline" onclick="return confirm('Đưa đơn vào danh sách chờ giường phụ?');">Vào waitlist</button>
                </form>

                <form method="POST" action="{{ route('staff.extra-bed-requests.resolve', $extraBedRequest->id) }}">
                    @csrf
                    <input type="hidden" name="choice" value="staff_cancel">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Hủy hẳn đơn này? Hệ thống sẽ tự hoàn tiền nếu khách đã thanh toán.');">Hủy đơn</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
