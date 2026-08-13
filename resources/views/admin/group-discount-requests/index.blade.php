@extends('layouts.admin')

@section('title', 'Ưu đãi đoàn · Homi Admin')
@section('page_title', 'Ưu đãi đoàn')
@section('page_subtitle', 'Lịch sử ưu đãi đoàn/nhóm áp dụng theo chính sách và các đề xuất giảm thêm của nhân viên.')

@section('content')
<div class="card">
    <form method="GET" class="filter-bar">
        <select name="status" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Chờ duyệt</option>
            <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Đã duyệt</option>
            <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Đã từ chối</option>
        </select>
        <select name="type" onchange="this.form.submit()">
            <option value="">Mọi nguồn</option>
            <option value="policy_tier" @selected(($filters['type'] ?? '') === 'policy_tier')>Tự động theo chính sách</option>
            <option value="staff_extra" @selected(($filters['type'] ?? '') === 'staff_extra')>Đề xuất/áp dụng thêm</option>
        </select>
    </form>

    @if ($requests->isEmpty())
        <div class="empty-box">Chưa có ưu đãi đoàn nào được ghi nhận.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Đơn</th>
                        <th>Nhân viên</th>
                        <th>Nguồn</th>
                        <th>Đề xuất</th>
                        <th>Đã duyệt</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        <tr>
                            <td>{{ $request->booking->booking_code }}</td>
                            <td>{{ $request->user->name ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $request->type === 'policy_tier' ? 'badge-blue' : 'badge-orange' }}">
                                    {{ $request->type === 'policy_tier' ? 'Tự động theo chính sách' : 'Đề xuất/áp dụng thêm' }}
                                </span>
                            </td>
                            <td>{{ (float) $request->requested_percent }}%</td>
                            <td>{{ $request->approved_percent !== null ? (float) $request->approved_percent . '%' : '—' }}</td>
                            <td>
                                @php
                                    $statusBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$request->status] ?? 'badge-green';
                                    $statusLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$request->status] ?? $request->status;
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            </td>
                            <td>
                                <a href="{{ route('admin.group-discount-requests.show', $request->id) }}" class="btn btn-outline btn-sm">Xem</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 16px;">{{ $requests->links() }}</div>
    @endif
</div>
@endsection
