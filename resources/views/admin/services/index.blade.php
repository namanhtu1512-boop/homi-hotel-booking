@extends('layouts.admin')

@section('title', 'Dịch vụ · Homi Admin')
@section('page_title', 'Dịch vụ')
@section('page_subtitle', 'Dịch vụ thêm khách có thể chọn khi đặt phòng (ăn sáng, đưa đón sân bay...).')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.services.create') }}" class="btn btn-primary">+ Tạo dịch vụ</a>
    </div>

    @if ($services->isEmpty())
        <div class="empty-box">Chưa có dịch vụ nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tên dịch vụ</th>
                        <th>Mô tả</th>
                        <th>Giá</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($services as $service)
                        <tr>
                            <td>{{ $service->name }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($service->description, 60) }}</td>
                            <td>{{ number_format($service->price, 0, ',', '.') }}đ</td>
                            <td>
                                @if ($service->trashed())
                                    <span class="badge badge-red">Đã xóa</span>
                                @else
                                    <span class="badge {{ $service->status === 'active' ? 'badge-green' : 'badge-orange' }}">{{ $service->status === 'active' ? 'Đang bán' : 'Ẩn' }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-row">
                                    @if ($service->trashed())
                                        <form method="POST" action="{{ route('admin.services.restore', $service->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-outline btn-sm">Khôi phục</button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.services.edit', $service->id) }}" class="btn btn-outline btn-sm">Sửa</a>
                                        <form method="POST" action="{{ route('admin.services.destroy', $service->id) }}" onsubmit="return confirm('Xóa dịch vụ này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
