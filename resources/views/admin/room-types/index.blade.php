@extends('layouts.admin')

@section('title', 'Loại phòng · Homi Admin')
@section('page_title', 'Quản lý loại phòng')
@section('page_subtitle', 'Thêm, sửa, đổi giá, đổi số lượng và xóa loại phòng.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">Danh sách</div>
            <h2 class="section-title">{{ $roomTypes->count() }} loại phòng</h2>
        </div>

        <a href="{{ route('admin.room-types.create') }}" class="btn btn-primary">+ Thêm loại phòng</a>
    </div>

    @if ($roomTypes->isEmpty())
        <div class="empty-box">Chưa có loại phòng nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Sức chứa</th>
                        <th>Giá / đêm</th>
                        <th>Tổng số phòng</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roomTypes as $room)
                        <tr>
                            <td><a href="{{ route('admin.room-types.show', $room->id) }}">{{ $room->name }}</a></td>
                            <td>{{ $room->capacity }} khách</td>
                            <td>{{ number_format($room->price_per_night, 0, ',', '.') }}đ</td>
                            <td>{{ $room->total_rooms }}</td>
                            <td>
                                @if ($room->status === 'active')
                                    <span class="badge badge-green">Đang hoạt động</span>
                                @elseif ($room->status === 'hidden')
                                    <span class="badge badge-orange">Đang ẩn</span>
                                @else
                                    <span class="badge badge-red">Bảo trì</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-row">
                                    <a href="{{ route('admin.room-types.edit', $room->id) }}" class="btn btn-outline btn-sm">Sửa</a>
                                    <form method="POST" action="{{ route('admin.room-types.destroy', $room->id) }}"
                                        onsubmit="return confirm('Xóa loại phòng &quot;{{ $room->name }}&quot;?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
