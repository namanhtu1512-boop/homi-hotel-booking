@extends('layouts.admin')

@section('title', 'Giá theo mùa · Homi Admin')
@section('page_title', 'Giá theo mùa')
@section('page_subtitle', 'Điều chỉnh giá phòng theo đợt cao điểm/thấp điểm, áp dụng cho 1 loại phòng hoặc tất cả.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.seasonal-rates.create') }}" class="btn btn-primary">+ Tạo đợt giá</a>
    </div>

    @if ($seasonalRates->isEmpty())
        <div class="empty-box">Chưa có bảng giá theo mùa nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tên đợt giá</th>
                        <th>Áp dụng cho</th>
                        <th>Khoảng ngày</th>
                        <th>Điều chỉnh</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($seasonalRates as $rate)
                        <tr>
                            <td>{{ $rate->label }}</td>
                            <td>{{ $rate->roomType?->name ?? 'Tất cả loại phòng' }}</td>
                            <td>{{ $rate->start_date->format('d/m/Y') }} - {{ $rate->end_date->format('d/m/Y') }}</td>
                            <td>
                                @if ($rate->adjustment_type === 'percent')
                                    +{{ (float) $rate->adjustment_value }}%
                                @else
                                    +{{ number_format($rate->adjustment_value, 0, ',', '.') }}đ/đêm
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $rate->status === 'active' ? 'badge-green' : 'badge-orange' }}">{{ $rate->status === 'active' ? 'Đang áp dụng' : 'Ẩn' }}</span>
                            </td>
                            <td>
                                <div class="action-row">
                                    <a href="{{ route('admin.seasonal-rates.edit', $rate->id) }}" class="btn btn-outline btn-sm">Sửa</a>
                                    <form method="POST" action="{{ route('admin.seasonal-rates.destroy', $rate->id) }}" onsubmit="return confirm('Xóa đợt giá này?');">
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
