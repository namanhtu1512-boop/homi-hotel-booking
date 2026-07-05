@extends('layouts.admin')

@section('title', 'Liên hệ · Homi Admin')
@section('page_title', 'Liên hệ khách hàng')
@section('page_subtitle', 'Tin nhắn gửi từ trang /contact.')

@section('content')
<div class="card">
    <form method="GET" class="filter-bar">
        <select name="status" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            <option value="new" @selected(($filters['status'] ?? '') === 'new')>Chưa đọc</option>
            <option value="read" @selected(($filters['status'] ?? '') === 'read')>Đã đọc</option>
        </select>
    </form>

    @if ($messages->isEmpty())
        <div class="empty-box">Chưa có liên hệ nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Người gửi</th>
                        <th>Liên hệ</th>
                        <th>Nội dung</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($messages as $message)
                        <tr>
                            <td>{{ $message->name }}</td>
                            <td>{{ $message->email }}<br>{{ $message->phone }}</td>
                            <td style="max-width: 360px;">{{ $message->message }}</td>
                            <td><span class="badge {{ $message->status === 'new' ? 'badge-orange' : 'badge-green' }}">{{ $message->status === 'new' ? 'Chưa đọc' : 'Đã đọc' }}</span></td>
                            <td>
                                <div class="action-row">
                                    @if ($message->status === 'new')
                                        <form method="POST" action="{{ route('admin.contact-messages.mark-read', $message->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline btn-sm">Đánh dấu đã đọc</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.contact-messages.destroy', $message->id) }}" onsubmit="return confirm('Xóa liên hệ này?');">
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
        <div style="margin-top: 16px;">{{ $messages->links() }}</div>
    @endif
</div>
@endsection
