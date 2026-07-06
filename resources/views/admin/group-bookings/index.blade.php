@extends('layouts.admin')

@section('title', 'Đặt đoàn/nhóm · Homi Admin')
@section('page_title', 'Yêu cầu đặt đoàn/nhóm')
@section('page_subtitle', 'Yêu cầu báo giá gửi từ trang /group-bookings — liên hệ khách rồi đánh dấu đã liên hệ.')

@section('content')
<div class="card">
    <form method="GET" class="filter-bar">
        <select name="status" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            <option value="new" @selected(($filters['status'] ?? '') === 'new')>Mới</option>
            <option value="contacted" @selected(($filters['status'] ?? '') === 'contacted')>Đã liên hệ</option>
        </select>
    </form>

    @if ($requests->isEmpty())
        <div class="empty-box">Chưa có yêu cầu đặt đoàn/nhóm nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Người liên hệ</th>
                        <th>Công ty</th>
                        <th>Liên hệ</th>
                        <th>Số khách</th>
                        <th>Ngày dự kiến</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        <tr>
                            <td>{{ $request->contact_name }}</td>
                            <td>{{ $request->company_name ?? '—' }}</td>
                            <td>{{ $request->email }}<br>{{ $request->phone }}</td>
                            <td>{{ $request->group_size }}</td>
                            <td>
                                @if ($request->check_in && $request->check_out)
                                    {{ $request->check_in->format('d/m/Y') }} - {{ $request->check_out->format('d/m/Y') }}
                                @else
                                    Chưa xác định
                                @endif
                            </td>
                            <td><span class="badge {{ $request->status === 'new' ? 'badge-orange' : 'badge-green' }}">{{ $request->status === 'new' ? 'Mới' : 'Đã liên hệ' }}</span></td>
                            <td>
                                <div class="action-row">
                                    @if ($request->status === 'new')
                                        <form method="POST" action="{{ route('admin.group-bookings.mark-contacted', $request->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline btn-sm">Đánh dấu đã liên hệ</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.group-bookings.destroy', $request->id) }}" onsubmit="return confirm('Xóa yêu cầu này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                    </form>
                                </div>
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
