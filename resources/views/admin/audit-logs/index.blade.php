@extends('layouts.admin')

@section('title', 'Nhật ký hệ thống · Homi Admin')
@section('page_title', 'Nhật ký hệ thống')
@section('page_subtitle', 'Tra cứu mọi thao tác của nhân viên/admin trên hệ thống — phục vụ khi có tranh chấp hoặc sự cố.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">Danh sách</div>
            <h2 class="section-title">{{ $logs->total() }} nhật ký</h2>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="filter-bar">
        <select name="user_id">
            <option value="" @selected(($filters['user_id'] ?? '') === '')>Tất cả nhân viên</option>
            @foreach ($staffs as $staff)
                <option value="{{ $staff->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $staff->id)>{{ $staff->name }}</option>
            @endforeach
        </select>

        <select name="action">
            <option value="" @selected(($filters['action'] ?? '') === '')>Tất cả hành động</option>
            @foreach ($actions as $code => $label)
                <option value="{{ $code }}" @selected(($filters['action'] ?? '') === $code)>{{ $label }}</option>
            @endforeach
        </select>

        <div class="form-group">
            <label for="created_from">Từ ngày</label>
            <input type="date" id="created_from" name="created_from" value="{{ $filters['created_from'] ?? '' }}">
        </div>
        <div class="form-group">
            <label for="created_to">Đến ngày</label>
            <input type="date" id="created_to" name="created_to" value="{{ $filters['created_to'] ?? '' }}">
        </div>

        <button type="submit" class="btn btn-outline">Lọc</button>

        @if (array_filter($filters))
            <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline">Xóa lọc</a>
        @endif
    </form>

    @if ($logs->isEmpty())
        <div class="empty-box">Không tìm thấy nhật ký nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Nhân viên</th>
                        <th>Hành động</th>
                        <th>Mô tả</th>
                        <th>Đơn liên quan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->user?->name ?? 'Hệ thống' }}</td>
                            <td>{{ $log->actionLabel() }}</td>
                            <td>{{ $log->description ?? '—' }}</td>
                            <td>
                                @if ($log->auditable_type === \App\Models\Booking::class)
                                    <a href="{{ route('admin.bookings.show', $log->auditable_id) }}" class="btn btn-outline btn-sm">Xem đơn</a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="action-row" style="margin-top: 16px;">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
