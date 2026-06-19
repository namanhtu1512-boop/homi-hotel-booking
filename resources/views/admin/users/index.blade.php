@extends('layouts.admin')

@section('title', 'Quản lý người dùng · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div><h1>👥 Quản lý người dùng</h1><p>Quản lý tài khoản khách hàng, nhân viên và quản trị viên</p></div>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="admin-toolbar">
        <input class="form-control toolbar-search" type="text" name="search" value="{{ $search }}" placeholder="🔍 Tìm theo tên, email...">
        <select class="form-control" name="role" onchange="this.form.submit()">
            <option value="" @selected($role === '')>Tất cả vai trò</option>
            <option value="customer" @selected($role === 'customer')>Khách hàng</option>
            <option value="staff" @selected($role === 'staff')>Nhân viên</option>
            <option value="admin" @selected($role === 'admin')>Quản trị viên</option>
        </select>
        <select class="form-control" name="status" onchange="this.form.submit()">
            <option value="" @selected($status === '')>Tất cả trạng thái</option>
            <option value="active" @selected($status === 'active')>Hoạt động</option>
            <option value="inactive" @selected($status === 'inactive')>Đã khóa</option>
        </select>
        <button type="submit" class="btn btn-outline">Lọc</button>
        @if ($search || $role || $status)
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Xóa lọc</a>
        @endif
    </form>

    <div class="data-card">
        <div class="data-card-header">Danh sách người dùng <span class="count-pill">{{ $users->total() }}</span></div>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Người dùng</th><th>Vai trò</th><th>Đặt phòng</th><th>Ngày tham gia</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="entity-cell">
                                    <div class="avatar-sm">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                    <div><div class="entity-name">{{ $user->name }}</div><div class="entity-sub">{{ $user->email }}</div></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $user->role === 'admin' ? 'badge-blue' : ($user->role === 'staff' ? 'badge-pending' : 'badge-confirmed') }}">
                                    {{ ['admin' => 'Quản trị viên', 'staff' => 'Nhân viên', 'customer' => 'Khách hàng'][$user->role] ?? $user->role }}
                                </span>
                            </td>
                            <td>{{ $user->bookings_count }}</td>
                            <td>{{ $user->created_at->format('d/m/Y') }}</td>
                            <td><span class="badge {{ $user->status === 'active' ? 'badge-confirmed' : 'badge-cancelled' }}">{{ $user->status === 'active' ? 'Hoạt động' : 'Đã khóa' }}</span></td>
                            <td>
                                <div class="row-actions">
                                    <a class="icon-action" title="Xem hồ sơ" href="{{ route('admin.users.show', $user) }}">👁️</a>
                                    @if ($user->id !== auth()->id() && auth()->user()->role === 'admin')
                                        <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" onsubmit="return confirm('{{ $user->status === 'active' ? 'Khóa' : 'Mở khóa' }} tài khoản &quot;{{ $user->name }}&quot;?');">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="icon-action {{ $user->status === 'active' ? 'danger' : 'success' }}" title="{{ $user->status === 'active' ? 'Khóa tài khoản' : 'Mở khóa' }}">
                                                {{ $user->status === 'active' ? '🔒' : '🔓' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><div class="icon">👥</div><h3>Không tìm thấy người dùng</h3><p>Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials._pagination', ['paginator' => $users])
    </div>
@endsection
