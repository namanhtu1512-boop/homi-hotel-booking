@extends('layouts.staff')

@section('title', 'Khuyến mãi · Homi Nhân viên')
@section('page_title', 'Khuyến mãi')
@section('page_subtitle', 'Danh sách mã giảm giá đang có để tư vấn cho khách — chỉ xem, gửi đề xuất mã mới ở "Ưu đãi đoàn cho khách quen".')

@section('content')
<div class="card">
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
                    </tr>
                </thead>
                <tbody>
                    @foreach ($promotions as $promo)
                        <tr>
                            <td>
                                {{ $promo->name }}
                                @if ($promo->stackable)
                                    <span class="badge badge-blue">Stack được</span>
                                @endif
                            </td>
                            <td>@include('partials._copy-code-badge', ['code' => $promo->code])</td>
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
                                <span class="badge {{ $promo->status === 'active' ? 'badge-green' : 'badge-orange' }}">{{ $promo->status === 'active' ? 'Đang chạy' : 'Đã ẩn' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
