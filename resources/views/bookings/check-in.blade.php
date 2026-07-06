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
