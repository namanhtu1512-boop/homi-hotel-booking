@if (($checkedInRooms ?? collect())->count() > 1)
    <select name="booking_item_room_id" class="input" style="width:150px;" title="Phòng nào phải trả khoản này">
        <option value="">-- Chọn phòng --</option>
        @foreach ($checkedInRooms as $bir)
            <option value="{{ $bir->id }}">Phòng {{ $bir->room->room_number ?? '—' }} ({{ $bir->bookingItem->roomType->name ?? '—' }})</option>
        @endforeach
    </select>
@endif
