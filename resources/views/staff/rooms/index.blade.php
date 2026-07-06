@extends('layouts.staff')

@section('title', 'Phòng vật lý · Homi Nhân viên')
@section('page_title', 'Phòng vật lý')
@section('page_subtitle', 'Cập nhật trạng thái dọn phòng theo từng số phòng.')

@section('content')
<div class="card">
    <form method="GET" class="filter-bar">
        <select name="room_type_id" onchange="this.form.submit()">
            <option value="">Tất cả loại phòng</option>
            @foreach ($roomTypes as $roomType)
                <option value="{{ $roomType->id }}" @selected(($filters['room_type_id'] ?? '') == $roomType->id)>{{ $roomType->name }}</option>
            @endforeach
        </select>
    </form>

    @if ($rooms->isEmpty())
        <div class="empty-box">Chưa có phòng vật lý nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Số phòng</th>
                        <th>Loại phòng</th>
                        <th>Trạng thái dọn phòng</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rooms as $room)
                        <tr>
                            <td>{{ $room->room_number }}</td>
                            <td>{{ $room->roomType->name ?? '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('staff.rooms.update-housekeeping', $room->id) }}" class="filter-bar">
                                    @csrf
                                    @method('PATCH')
                                    <select name="housekeeping_status" onchange="this.form.submit()">
                                        <option value="clean" @selected($room->housekeeping_status === 'clean')>Sạch</option>
                                        <option value="dirty" @selected($room->housekeeping_status === 'dirty')>Cần dọn</option>
                                        <option value="inspected" @selected($room->housekeeping_status === 'inspected')>Đã kiểm tra</option>
                                        <option value="maintenance" @selected($room->housekeeping_status === 'maintenance')>Bảo trì</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
