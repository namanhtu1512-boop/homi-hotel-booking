@extends('layouts.admin')

@section('title', 'Nhật ký hoạt động · Homi Admin')
@section('page_title', 'Nhật ký hoạt động')
@section('page_subtitle', 'Lịch sử các thao tác quản trị nhạy cảm: khóa tài khoản, sửa thông tin khách sạn, loại phòng...')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">Nhật ký</div>
            <h2 class="section-title">{{ $logs->total() }} hành động</h2>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="filter-bar">
        <input type="text" name="action" value="{{ $action }}" placeholder="Lọc theo action (vd: hotel_info.updated)...">
        <button type="submit" class="btn btn-outline">Lọc</button>

        @if ($action)
            <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline">Xóa lọc</a>
        @endif
    </form>

    @if ($logs->isEmpty())
        <div class="empty-box">Chưa có hoạt động nào được ghi nhận.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Người thực hiện</th>
                        <th>Hành động</th>
                        <th>Mô tả</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->user?->name ?? '—' }}</td>
                            <td><span class="badge badge-blue">{{ $log->action }}</span></td>
                            <td>{{ $log->description }}</td>
                            <td>{{ $log->ip_address }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="action-row" style="margin-top: 16px;">
            {{ $logs->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection
