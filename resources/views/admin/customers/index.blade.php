@extends('layouts.admin')

@section('title', 'Khách hàng · Homi Admin')
@section('page_title', 'Quản lý khách hàng')
@section('page_subtitle', 'Tìm kiếm, khóa/mở khóa và xem lịch sử đặt phòng của từng khách hàng.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">Danh sách</div>
            <h2 class="section-title">{{ $customers->total() }} khách hàng</h2>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.customers.index') }}" class="filter-bar">
        <input type="text" name="search" value="{{ $search }}" placeholder="Tìm theo tên hoặc email...">
        <button type="submit" class="btn btn-outline">Lọc</button>

        @if ($search)
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
                        <th>Số đơn đã đặt</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td><a href="{{ route('admin.customers.show', $customer->id) }}">{{ $customer->name }}</a></td>
                            <td>{{ $customer->email }}</td>
                            <td>{{ $customer->bookings_count }}</td>
                            <td>
                                @if ($customer->status === 'active')
                                    <span class="badge badge-green">Đang hoạt động</span>
                                @else
                                    <span class="badge badge-red">Đã khóa</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-outline btn-sm">Xem chi tiết</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="action-row" style="margin-top: 16px;">
            {{ $customers->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection
