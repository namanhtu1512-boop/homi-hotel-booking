@extends('layouts.admin')

@section('title', 'Người dùng · Homi Admin')
@section('page_title', 'Quản lý người dùng')
@section('page_subtitle', 'Lọc theo vai trò, tìm kiếm và khóa/mở khóa tài khoản.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">Danh sách</div>
            <h2 class="section-title">{{ $users->total() }} người dùng</h2>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="filter-bar">
        <input type="text" name="search" value="{{ $search }}" placeholder="Tìm theo tên hoặc email...">

        <select name="role">
            <option value="" @selected($role === '')>Tất cả vai trò</option>
            <option value="customer" @selected($role === 'customer')>Customer</option>
            <option value="staff" @selected($role === 'staff')>Staff</option>
            <option value="admin" @selected($role === 'admin')>Admin</option>
        </select>

        <button type="submit" class="btn btn-outline">Lọc</button>

        @if ($search || $role)
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Xóa lọc</a>
        @endif
    </form>

    @if ($users->isEmpty())
        <div class="empty-box">Không tìm thấy người dùng nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge badge-blue">{{ $user->role }}</span></td>
                            <td>
                                @if ($user->status === 'active')
                                    <span class="badge badge-green">Đang hoạt động</span>
                                @else
                                    <span class="badge badge-red">Đã khóa</span>
                                @endif
                            </td>
                            <td>
                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.toggle-status', $user->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline btn-sm">
                                            {{ $user->status === 'active' ? 'Khóa' : 'Mở khóa' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="action-row" style="margin-top: 16px;">
            {{ $users->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection
