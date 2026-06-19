@extends('layouts.admin')

@section('title', 'Quản lý khách sạn · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div><h1>🏨 Quản lý khách sạn</h1><p>Thêm, chỉnh sửa, xóa mềm/khôi phục và ẩn/hiện khách sạn</p></div>
        <div class="admin-page-actions"><a href="{{ route('admin.hotels.create') }}" class="btn btn-primary">➕ Thêm khách sạn</a></div>
    </div>

    <form method="GET" action="{{ route('admin.hotels.index') }}" class="admin-toolbar">
        <input class="form-control toolbar-search" type="text" name="search" value="{{ $search }}" placeholder="🔍 Tìm theo tên hoặc thành phố...">
        <select class="form-control" name="status" onchange="this.form.submit()">
            <option value="" @selected($status === '')>Tất cả trạng thái</option>
            <option value="active" @selected($status === 'active')>Đang hoạt động</option>
            <option value="hidden" @selected($status === 'hidden')>Đang ẩn</option>
            <option value="deleted" @selected($status === 'deleted')>Đã xóa mềm</option>
        </select>
        <button type="submit" class="btn btn-outline">Lọc</button>
        @if ($search || $status)
            <a href="{{ route('admin.hotels.index') }}" class="btn btn-ghost">Xóa lọc</a>
        @endif
    </form>

    <div class="data-card">
        <div class="data-card-header">Danh sách khách sạn <span class="count-pill">{{ $hotels->total() }}</span></div>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Khách sạn</th><th>Hạng sao</th><th>Loại phòng</th><th>Trạng thái</th><th>Ngày tạo</th><th></th></tr></thead>
                <tbody>
                    @forelse ($hotels as $hotel)
                        <tr>
                            <td>
                                <a href="{{ route('admin.hotels.show', $hotel->id) }}" style="text-decoration:none;color:inherit">
                                    <div class="entity-cell">
                                        <div class="entity-icon">
                                            @if ($hotel->images->isNotEmpty())
                                                <img src="{{ $hotel->images->first()->path }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius)">
                                            @else
                                                🏨
                                            @endif
                                        </div>
                                        <div>
                                            <div class="entity-name">{{ $hotel->name }}</div>
                                            <div class="entity-sub">{{ $hotel->city }}@if($hotel->district) · {{ $hotel->district }}@endif</div>
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td>
                                @if ($hotel->star_rating)
                                    <span class="stars">{{ str_repeat('★', $hotel->star_rating) }}</span><span class="stars-muted">{{ str_repeat('★', 5 - $hotel->star_rating) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $hotel->room_types_count }} loại</td>
                            <td>
                                @if ($hotel->trashed())
                                    <span class="badge badge-cancelled">Đã xóa mềm</span>
                                @elseif ($hotel->status === 'active')
                                    <span class="badge badge-confirmed">Hoạt động</span>
                                @else
                                    <span class="badge badge-pending">Đã ẩn</span>
                                @endif
                            </td>
                            <td>{{ $hotel->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="row-actions">
                                    @if ($hotel->trashed())
                                        @if (auth()->user()->role === 'admin')
                                            <form method="POST" action="{{ route('admin.hotels.restore', $hotel->id) }}">
                                                @csrf
                                                <button type="submit" class="icon-action success" title="Khôi phục">↩️</button>
                                            </form>
                                        @endif
                                    @else
                                        <a class="icon-action" title="Xem chi tiết" href="{{ route('admin.hotels.show', $hotel->id) }}">👁️</a>
                                        <a class="icon-action" title="Sửa" href="{{ route('admin.hotels.edit', $hotel->id) }}">✏️</a>
                                        <form method="POST" action="{{ route('admin.hotels.toggle-status', $hotel->id) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="icon-action" title="{{ $hotel->status === 'active' ? 'Ẩn' : 'Hiện' }}">{{ $hotel->status === 'active' ? '🙈' : '👁️‍🗨️' }}</button>
                                        </form>
                                        @if (auth()->user()->role === 'admin')
                                            <form method="POST" action="{{ route('admin.hotels.destroy', $hotel->id) }}" onsubmit="return confirm('Xóa mềm khách sạn &quot;{{ $hotel->name }}&quot;?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="icon-action danger" title="Xóa mềm">🗑️</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><div class="icon">🏨</div><h3>Không tìm thấy khách sạn</h3><p>Thử thay đổi bộ lọc hoặc thêm khách sạn mới</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials._pagination', ['paginator' => $hotels])
    </div>
@endsection
