@extends('layouts.admin')

@section('title', 'Phụ phí phát sinh · Homi Admin')
@section('page_title', 'Phụ phí phát sinh')
@section('page_subtitle', 'Danh mục vật dụng/khoản bồi thường nhân viên có thể chọn nhanh khi ghi phụ phí phát sinh cho đơn đang lưu trú.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.surcharge-items.create') }}" class="btn btn-primary">+ Tạo mục phụ phí</a>
    </div>

    @if ($surchargeItems->isEmpty())
        <div class="empty-box">Chưa có mục phụ phí nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Phân loại</th>
                        <th>Nhóm</th>
                        <th>Giá</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $categoryBadges = [
                            'damage' => ['🔴 Hỏng/mất đồ', 'badge-red'],
                            'violation' => ['🟠 Vi phạm', 'badge-orange'],
                            'cleaning' => ['🟡 Vệ sinh đặc biệt', 'badge-blue'],
                        ];
                    @endphp
                    @foreach ($surchargeItems as $item)
                        @php [$categoryLabel, $categoryBadge] = $categoryBadges[$item->category?->value] ?? ['—', 'badge-orange']; @endphp
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td><span class="badge {{ $categoryBadge }}">{{ $categoryLabel }}</span></td>
                            <td>{{ $item->group ?? '—' }}</td>
                            <td>
                                @if ($item->price !== null)
                                    {{ number_format($item->price, 0, ',', '.') }}đ
                                @else
                                    <span class="text-slate-400">{{ $item->price_note ?? 'Tùy trường hợp' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($item->trashed())
                                    <span class="badge badge-red">Đã xóa</span>
                                @else
                                    <span class="badge {{ $item->status === 'active' ? 'badge-green' : 'badge-orange' }}">{{ $item->status === 'active' ? 'Đang dùng' : 'Ẩn' }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-row">
                                    @if ($item->trashed())
                                        <form method="POST" action="{{ route('admin.surcharge-items.restore', $item->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-outline btn-sm">Khôi phục</button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.surcharge-items.edit', $item->id) }}" class="btn btn-outline btn-sm">Sửa</a>
                                        <form method="POST" action="{{ route('admin.surcharge-items.destroy', $item->id) }}" onsubmit="return confirm('Xóa mục phụ phí này?');">
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
