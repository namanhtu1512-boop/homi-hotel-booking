@extends('layouts.admin')

@section('title', 'Khách hàng · Homi Admin')
@section('page_title', 'Quản lý khách hàng')
@section('page_subtitle', 'Tìm kiếm khách hàng và xem lịch sử đặt phòng của từng người.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">Danh sách</div>
            <h2 class="section-title">{{ $customers->total() }} khách hàng</h2>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.customers.index') }}" class="filter-bar">
        <input type="text" name="search" value="{{ $search }}" placeholder="Tìm theo tên, email hoặc SĐT...">

        <select name="status">
            <option value="" @selected($status === '')>Tất cả trạng thái</option>
            <option value="active" @selected($status === 'active')>Đang hoạt động</option>
            <option value="locked" @selected($status === 'locked')>Đã khóa</option>
        </select>

        <button type="submit" class="btn btn-outline">Lọc</button>

        @if ($search || $status)
            <a href="{{ route('admin.customers.index') }}" class="btn btn-outline">Xóa lọc</a>
        @endif
    </form>

    @if ($customers->isEmpty())
        <div class="empty-box">Không tìm thấy khách hàng nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Email</th>
                        <th>Điện thoại</th>
                        <th>Số đơn đã đặt</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->email }}</td>
                            <td>{{ $customer->phone ?: '—' }}</td>
                            <td>{{ $customer->bookings_count }}</td>
                            <td>
                                @if ($customer->status === 'active')
                                    <span class="badge badge-green">Đang hoạt động</span>
                                @else
                                    <span class="badge badge-red">Đã khóa</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-outline btn-sm">Xem lịch sử</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="action-row" style="margin-top: 16px;">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection
