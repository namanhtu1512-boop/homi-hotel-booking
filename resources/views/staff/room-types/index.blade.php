@extends('layouts.staff')

@section('title', 'Loại phòng · Homi Nhân viên')
@section('page_title', 'Quản lý loại phòng')
@section('page_subtitle', 'Thêm, sửa và theo dõi tồn phòng.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">Danh sách</div>
            <h2 class="section-title">{{ $roomTypes->count() }} loại phòng</h2>
        </div>

        <a href="{{ route('staff.room-types.create') }}" class="btn btn-primary">+ Thêm loại phòng</a>
    </div>

    <form method="GET" action="{{ route('staff.room-types.index') }}" class="filter-bar mb-4">
        <div>
            <label class="form-label" for="check_in">Từ ngày</label>
            <input class="input" type="date" id="check_in" name="check_in" value="{{ $filters['check_in'] }}">
        </div>
        <div>
            <label class="form-label" for="check_out">Đến ngày</label>
            <input class="input" type="date" id="check_out" name="check_out" value="{{ $filters['check_out'] }}">
        </div>
        <button type="submit" class="btn btn-outline">Kiểm tra</button>
        @if ($dateRangeApplied)
            <a href="{{ route('staff.room-types.index') }}" class="btn btn-outline">Xóa lọc</a>
        @endif
    </form>

    @if ($dateRangeError)
        <div class="alert alert-danger mb-4">{{ $dateRangeError }}</div>
    @endif

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
                        <th>
                            @if ($dateRangeApplied)
                                Còn trống ({{ \Illuminate\Support\Carbon::parse($filters['check_in'])->format('d/m/Y') }} - {{ \Illuminate\Support\Carbon::parse($filters['check_out'])->format('d/m/Y') }})
                            @else
                                Còn trống hôm nay
                            @endif
                        </th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roomTypes as $room)
                        <tr>
                            <td>{{ $room->name }}</td>
                            <td>{{ $room->capacity }} khách</td>
                            <td>{{ number_format($room->price_per_night, 0, ',', '.') }}đ</td>
                            <td>{{ $room->total_rooms }}</td>
                            @php
                                $availableCount = $dateRangeApplied ? $room->available_range : $room->available_today;
                            @endphp
                            <td>
                                @if ($room->status !== 'active')
                                    <span class="text-slate-400">—</span>
                                @elseif ($availableCount > 0)
                                    <span class="badge badge-green">{{ $availableCount }}</span>
                                @else
                                    <span class="badge badge-red">Hết phòng</span>
                                @endif
                            </td>
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
                                    <a href="{{ route('staff.room-types.show', $room->id) }}" class="btn btn-outline btn-sm">Xem</a>
                                    <a href="{{ route('staff.room-types.edit', $room->id) }}" class="btn btn-outline btn-sm">Sửa</a>
                                    <form method="POST" action="{{ route('staff.room-types.toggle-status', $room->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline btn-sm">
                                            {{ $room->status === 'active' ? 'Ẩn' : 'Hiện' }}
                                        </button>
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
