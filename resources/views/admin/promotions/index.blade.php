@extends('layouts.admin')

@section('title', 'Khuyến mãi · Homi Admin')
@section('page_title', 'Khuyến mãi')
@section('page_subtitle', 'Quản lý mã giảm giá áp dụng khi khách đặt phòng.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.promotions.create') }}" class="btn btn-primary">+ Tạo khuyến mãi</a>
    </div>

    @if ($promotions->isEmpty())
        <div class="empty-box">Chưa có khuyến mãi nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Mã</th>
                        <th>Giảm giá</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($promotions as $promo)
                        <tr>
                            <td>{{ $promo->name }}</td>
                            <td><span class="badge badge-blue">{{ $promo->code }}</span></td>
                            <td>
                                @if ($promo->discount_percent)
                                    {{ (float) $promo->discount_percent }}%
                                @elseif ($promo->discount_amount)
                                    {{ number_format($promo->discount_amount, 0, ',', '.') }}đ
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                {{ $promo->starts_at?->format('d/m/Y') ?? '—' }} - {{ $promo->ends_at?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td>
                                @if ($promo->trashed())
                                    <span class="badge badge-red">Đã xóa</span>
                                @else
                                    <span class="badge {{ $promo->status === 'active' ? 'badge-green' : 'badge-orange' }}">{{ $promo->status === 'active' ? 'Đang chạy' : 'Đã ẩn' }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-row">
                                    @if ($promo->trashed())
                                        <form method="POST" action="{{ route('admin.promotions.restore', $promo->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-outline btn-sm">Khôi phục</button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.promotions.edit', $promo->id) }}" class="btn btn-outline btn-sm">Sửa</a>
                                        <form method="POST" action="{{ route('admin.promotions.destroy', $promo->id) }}" onsubmit="return confirm('Xóa khuyến mãi này?');">
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
