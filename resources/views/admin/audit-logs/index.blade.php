@extends('layouts.admin')

@section('title', 'Nhật ký hoạt động · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div><h1>🕒 Nhật ký hoạt động</h1><p>Theo dõi các thao tác quản trị: ai làm gì, lúc nào</p></div>
    </div>

    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="admin-toolbar">
        <input class="form-control toolbar-search" type="text" name="action" value="{{ $action }}" placeholder="🔍 Tìm theo hành động (vd: hotel.created)...">
        <select class="form-control" name="user_id" onchange="this.form.submit()">
            <option value="">Tất cả người dùng</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) $userId === (string) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline">Lọc</button>
        @if ($action || $userId)
            <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-ghost">Xóa lọc</a>
        @endif
    </form>

    <div class="data-card">
        <div class="data-card-header">Lịch sử thao tác <span class="count-pill">{{ $logs->total() }}</span></div>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Thời gian</th><th>Người thực hiện</th><th>Hành động</th><th>Mô tả</th><th>IP</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->user->name ?? 'Hệ thống' }}</td>
                            <td><span class="badge badge-blue">{{ $log->action }}</span></td>
                            <td>{{ $log->description }}</td>
                            <td class="text-muted">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state"><div class="icon">🕒</div><h3>Chưa có hoạt động nào được ghi nhận</h3></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials._pagination', ['paginator' => $logs])
    </div>
@endsection
