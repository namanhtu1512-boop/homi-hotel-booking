@extends('layouts.app')

@section('title', 'Quản lý tài khoản · Homi')
@section('banner_tag', 'Admin · Users')
@section('banner_title', 'Quản lý tài khoản')
@section('banner_subtitle', 'Tìm kiếm, lọc vai trò và khóa/mở khóa tài khoản.')

@section('content')
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <form method="GET" action="{{ route('admin.users.index') }}" class="filter-bar">
        <input type="text" name="search" value="{{ $search }}" placeholder="Tìm theo tên hoặc email...">

        <select name="role">
            <option value="" @selected($role === '')>Tất cả vai trò</option>
            <option value="customer" @selected($role === 'customer')>Customer</option>
            <option value="staff" @selected($role === 'staff')>Staff</option>
            <option value="admin" @selected($role === 'admin')>Admin</option>
        </select>

        <button type="submit" class="btn btn-outline">Lọc</button>
    </form>

    @if ($users->isEmpty())
        <div class="empty-box">Không tìm thấy tài khoản nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        @if (auth()->user()->role === 'admin')
                            <th></th>
                        @endif
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
                            @if (auth()->user()->role === 'admin')
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
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="action-row" style="margin-top: 18px;">
            {{ $users->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection
