@extends($layout)

@section('title', 'Check-in đơn ' . $booking->booking_code)
@section('page_title', 'Check-in đơn ' . $booking->booking_code)
@section('page_subtitle', 'Chọn đúng số phòng vật lý cho từng loại phòng trong đơn.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ $backRoute }}" class="btn btn-outline">Quay lại</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @php
        $approvedEarlyCheckin = $booking->earlyCheckinRequests->firstWhere('status', 'approved');
    @endphp
    @if ($approvedEarlyCheckin)
        <div class="alert alert-success">
            ✅ Đã duyệt nhận phòng sớm lúc {{ substr($approvedEarlyCheckin->requested_arrival_time, 0, 5) }}
            (phụ phí {{ number_format($approvedEarlyCheckin->fee_amount, 0, ',', '.') }}đ đã cộng vào đơn).
        </div>
    @endif

    <div class="form-group">
        <label>Đối chiếu thông tin khách trước khi giao phòng</label>
        <div class="info-list">
            <div class="info-item">
                <span class="label">Họ tên</span>
                <span class="value">{{ $booking->customer_name }}</span>
            </div>
            <div class="info-item">
                <span class="label">Điện thoại</span>
                <span class="value">{{ $booking->customer_phone }}</span>
            </div>
            <div class="info-item">
                <span class="label">Số CCCD/CMND</span>
                <span class="value">{{ $booking->national_id ?: '— (chưa có, kiểm tra CCCD khách xuất trình rồi đối chiếu tên/thông tin thủ công)' }}</span>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}">
        @csrf

        @foreach ($booking->bookingItems as $item)
            <div class="form-group">
                <label>
                    {{ $item->roomType->name ?? 'Loại phòng #' . $item->room_type_id }}
                    — cần chọn đúng <strong>{{ $item->quantity }}</strong> phòng
                </label>

                @php $available = $availableRooms->get($item->id, collect()); @endphp

                @if ($available->isEmpty())
                    <div class="empty-box">Không còn phòng trống nào của loại này để gán.</div>
                @else
                    <div class="checkbox-grid">
                        @foreach ($available as $room)
                            <label class="checkbox-item">
                                <input type="checkbox" name="rooms[{{ $item->id }}][]" value="{{ $room->id }}">
                                Phòng {{ $room->room_number }}
                                <span class="badge {{ $room->housekeeping_status === 'clean' ? 'badge-green' : 'badge-orange' }}">{{ $room->housekeeping_status }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        <button type="submit" class="btn btn-primary btn-block">Xác nhận check-in</button>
    </form>
</div>
@endsection
