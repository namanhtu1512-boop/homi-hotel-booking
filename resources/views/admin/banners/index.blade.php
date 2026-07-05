@extends('layouts.admin')

@section('title', 'Banner · Homi Admin')
@section('page_title', 'Banner trang chủ')
@section('page_subtitle', 'Ảnh hero hiển thị luân phiên ở đầu trang chủ, sắp theo thứ tự.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">+ Thêm banner</a>
    </div>

    @if ($banners->isEmpty())
        <div class="empty-box">Chưa có banner nào — trang chủ sẽ dùng ảnh mặc định.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Tiêu đề</th>
                        <th>Thứ tự</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($banners as $banner)
                        <tr>
                            <td><img src="{{ $banner->image_url }}" alt="" style="width: 96px; height: 56px; object-fit: cover; border-radius: 8px;"></td>
                            <td>
                                {{ $banner->title }}
                                @if ($banner->subtitle)
                                    <div class="section-desc">{{ $banner->subtitle }}</div>
                                @endif
                            </td>
                            <td>{{ $banner->sort_order }}</td>
                            <td><span class="badge {{ $banner->status === 'active' ? 'badge-green' : 'badge-orange' }}">{{ $banner->status === 'active' ? 'Hiển thị' : 'Đã ẩn' }}</span></td>
                            <td>
                                <div class="action-row">
                                    <a href="{{ route('admin.banners.edit', $banner->id) }}" class="btn btn-outline btn-sm">Sửa</a>
                                    <form method="POST" action="{{ route('admin.banners.destroy', $banner->id) }}" onsubmit="return confirm('Xóa banner này?');">
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
    @endif
</div>
@endsection
