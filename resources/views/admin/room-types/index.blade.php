@extends('layouts.app')

@section('title', 'Quản lý loại phòng · Homi')
@section('banner_tag', 'Admin · Room Types')
@section('banner_title', 'Quản lý loại phòng')
@section('banner_subtitle', 'Thêm, sửa, ẩn/hiện và xóa mềm loại phòng.')

@section('content')
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">Danh sách</div>
            <h2 class="section-title" style="margin-bottom: 6px;">{{ $roomTypes->count() }} loại phòng</h2>
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
                        <th>Giá / đêm</th>
                        <th>Sức chứa</th>
                        <th>Tổng số phòng</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roomTypes as $roomType)
                        <tr>
                            <td>{{ $roomType->name }}</td>
                            <td>{{ number_format($roomType->price_per_night, 0, ',', '.') }}đ</td>
                            <td>{{ $roomType->capacity }}</td>
                            <td>{{ $roomType->total_rooms }}</td>
                            <td>
                                @if ($roomType->status === 'active')
                                    <span class="badge badge-green">Đang hoạt động</span>
                                @elseif ($roomType->status === 'hidden')
                                    <span class="badge badge-orange">Đang ẩn</span>
                                @else
                                    <span class="badge badge-red">Bảo trì</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-row">
                                    <a href="{{ route('admin.room-types.edit', $roomType->id) }}" class="btn btn-outline btn-sm">Sửa</a>

                                    <form method="POST" action="{{ route('admin.room-types.toggle-status', $roomType->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline btn-sm">
                                            {{ $roomType->status === 'active' ? 'Ẩn' : 'Hiện' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.room-types.destroy', $roomType->id) }}"
                                        onsubmit="return confirm('Xóa loại phòng &quot;{{ $roomType->name }}&quot;?');">
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
