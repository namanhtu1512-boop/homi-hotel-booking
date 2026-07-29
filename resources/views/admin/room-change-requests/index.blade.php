@extends('layouts.admin')

@section('title', 'Yêu cầu đổi phòng · Homi Admin')
@section('page_title', 'Yêu cầu đổi phòng')
@section('page_subtitle', 'Khách gửi yêu cầu đổi loại phòng/ngày ở cho đơn đã đặt — duyệt hoặc từ chối.')

@section('content')
<div class="card">
    <form method="GET" class="filter-bar">
        <select name="status" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Chờ duyệt</option>
            <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Đã duyệt</option>
            <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Đã từ chối</option>
        </select>
    </form>

    @if ($requests->isEmpty())
        <div class="empty-box">Chưa có yêu cầu đổi phòng nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Đơn</th>
                        <th>Khách</th>
                        <th>Loại phòng</th>
                        <th>Ngày ở</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        <tr>
                            <td>{{ $request->booking->booking_code }}</td>
                            <td>{{ $request->user->name }}</td>
                            <td>
                                {{ $request->currentRoomType->name ?? '—' }}
                                @if ($request->requestedRoomType)
                                    → {{ $request->requestedRoomType->name }}
                                @endif
                            </td>
                            <td>
                                @if ($request->requested_check_in && $request->requested_check_out)
                                    {{ $request->requested_check_in->format('d/m/Y') }} - {{ $request->requested_check_out->format('d/m/Y') }}
                                @else
                                    Giữ nguyên
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$request->status] ?? 'badge-green';
                                    $statusLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$request->status] ?? $request->status;
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            </td>
                            <td>
                                <a href="{{ route('admin.room-change-requests.show', $request->id) }}" class="btn btn-outline btn-sm">Xem</a>
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
