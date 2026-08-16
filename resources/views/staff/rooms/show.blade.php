@extends('layouts.staff')

@section('title', 'Phòng ' . $room->room_number . ' · Homi Nhân viên')
@section('page_title', 'Phòng ' . $room->room_number)
@section('page_subtitle', 'Thông tin và lịch sử thay đổi của phòng.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('staff.rooms.index') }}" class="btn btn-outline">Quay lại danh sách</a>
    </div>

    <div class="info-list">
        <div class="info-item">
            <span class="label">Số phòng</span>
            <span class="value">{{ $room->room_number }}</span>
        </div>
        <div class="info-item">
            <span class="label">Loại phòng</span>
            <span class="value">{{ $room->roomType->name ?? '—' }}</span>
        </div>
        <div class="info-item">
            <span class="label">Trạng thái dọn phòng</span>
            <span class="badge {{ $room->housekeeping_status === 'clean' ? 'badge-green' : 'badge-orange' }}">{{ $room->housekeeping_status }}</span>
        </div>
        <div class="info-item">
            <span class="label">Đang có khách</span>
            <span class="value">{{ $room->isOccupied() ? 'Có' : 'Không' }}</span>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 16px;">
    <div class="section-kicker">Lịch sử thay đổi</div>

    @if ($history->isEmpty())
        <div class="empty-box">Chưa có lịch sử nào cho phòng này.</div>
    @else
        <div class="table-wrapper" style="margin-top: 10px;">
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Người thực hiện</th>
                        <th>Hành động</th>
                        <th>Mô tả</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($history as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->user->name ?? 'Hệ thống' }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
