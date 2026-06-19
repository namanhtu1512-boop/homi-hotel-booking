@extends('layouts.admin')

@section('title', 'Quản lý loại phòng · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div><h1>🛏️ Quản lý loại phòng</h1><p>Quản lý giá, sức chứa và số lượng từng loại phòng</p></div>
        @if (auth()->user()->role === 'admin')
            <div class="admin-page-actions"><a href="{{ route('admin.room-types.create') }}" class="btn btn-primary">➕ Thêm loại phòng</a></div>
        @endif
    </div>

    <form method="GET" action="{{ route('admin.room-types.index') }}" class="admin-toolbar">
        <input class="form-control toolbar-search" type="text" name="search" value="{{ $search }}" placeholder="🔍 Tìm theo tên loại phòng...">
        <select class="form-control" name="hotel_id" onchange="this.form.submit()">
            <option value="">Tất cả khách sạn</option>
            @foreach ($hotels as $hotel)
                <option value="{{ $hotel->id }}" @selected((string) $hotelId === (string) $hotel->id)>{{ $hotel->name }}</option>
            @endforeach
        </select>
        <select class="form-control" name="status" onchange="this.form.submit()">
            <option value="" @selected($status === '')>Tất cả trạng thái</option>
            <option value="active" @selected($status === 'active')>Hoạt động</option>
            <option value="hidden" @selected($status === 'hidden')>Đã ẩn</option>
        </select>
        <button type="submit" class="btn btn-outline">Lọc</button>
        @if ($search || $status || $hotelId)
            <a href="{{ route('admin.room-types.index') }}" class="btn btn-ghost">Xóa lọc</a>
        @endif
    </form>

    <div class="data-card">
        <div class="data-card-header">Danh sách loại phòng <span class="count-pill">{{ $roomTypes->total() }}</span></div>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Loại phòng</th><th>Khách sạn</th><th>Sức chứa</th><th>Giá/đêm</th><th>Số lượng</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                    @forelse ($roomTypes as $roomType)
                        <tr>
                            <td><div class="entity-cell"><div class="entity-icon">🛏️</div><div class="entity-name">{{ $roomType->name }}</div></div></td>
                            <td>{{ $roomType->hotel->name ?? '—' }}</td>
                            <td>{{ $roomType->capacity }} khách</td>
                            <td style="font-weight:700">{{ number_format($roomType->price_per_night) }}đ<span style="color:var(--muted);font-weight:400">/đêm</span></td>
                            <td>{{ $roomType->total_rooms }} phòng</td>
                            <td>
                                @if ($roomType->trashed())
                                    <span class="badge badge-cancelled">Đã xóa mềm</span>
                                @elseif ($roomType->status === 'active')
                                    <span class="badge badge-confirmed">Hoạt động</span>
                                @else
                                    <span class="badge badge-pending">Đã ẩn</span>
                                @endif
                            </td>
                            <td>
                                <div class="row-actions">
                                    @if ($roomType->trashed())
                                        @if (auth()->user()->role === 'admin')
                                            <form method="POST" action="{{ route('admin.room-types.restore', $roomType->id) }}">
                                                @csrf
                                                <button type="submit" class="icon-action success" title="Khôi phục">↩️</button>
                                            </form>
                                        @endif
                                    @else
                                        <a class="icon-action" title="Sửa" href="{{ route('admin.room-types.edit', $roomType->id) }}">✏️</a>
                                        <form method="POST" action="{{ route('admin.room-types.toggle-status', $roomType->id) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="icon-action" title="{{ $roomType->status === 'active' ? 'Ẩn' : 'Hiện' }}">{{ $roomType->status === 'active' ? '🙈' : '👁️‍🗨️' }}</button>
                                        </form>
                                        @if (auth()->user()->role === 'admin')
                                            <form method="POST" action="{{ route('admin.room-types.destroy', $roomType->id) }}" onsubmit="return confirm('Xóa/ẩn loại phòng &quot;{{ $roomType->name }}&quot;?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="icon-action danger" title="Xóa">🗑️</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state"><div class="icon">🛏️</div><h3>Không tìm thấy loại phòng</h3><p>Thử thay đổi bộ lọc hoặc thêm loại phòng mới</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials._pagination', ['paginator' => $roomTypes])
    </div>
@endsection
