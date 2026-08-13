@extends('layouts.admin')

@section('title', 'Chính sách ưu đãi đoàn · Homi Admin')
@section('page_title', 'Chính sách ưu đãi đoàn')
@section('page_subtitle', 'Ưu đãi tự động theo số phòng — áp dụng ngay khi nhân viên tạo đơn, không cần duyệt.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.group-discount-policies.create') }}" class="btn btn-primary">+ Tạo chính sách</a>
    </div>

    @if ($policies->isEmpty())
        <div class="empty-box">Chưa có chính sách ưu đãi đoàn nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Áp dụng từ</th>
                        <th>Giảm giá</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($policies as $policy)
                        <tr>
                            <td>{{ $policy->name ?: '—' }}</td>
                            <td>≥ {{ $policy->min_rooms }} phòng</td>
                            <td>{{ (float) $policy->discount_percent }}%</td>
                            <td>
                                @if ($policy->trashed())
                                    <span class="badge badge-red">Đã xóa</span>
                                @else
                                    <span class="badge {{ $policy->status === 'active' ? 'badge-green' : 'badge-orange' }}">{{ $policy->status === 'active' ? 'Đang áp dụng' : 'Đã tắt' }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-row">
                                    @if ($policy->trashed())
                                        <form method="POST" action="{{ route('admin.group-discount-policies.restore', $policy->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-outline btn-sm">Khôi phục</button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.group-discount-policies.edit', $policy->id) }}" class="btn btn-outline btn-sm">Sửa</a>
                                        <form method="POST" action="{{ route('admin.group-discount-policies.destroy', $policy->id) }}" onsubmit="return confirm('Xóa chính sách này?');">
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
