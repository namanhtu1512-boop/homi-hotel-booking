@extends('layouts.admin')

@section('title', 'Khuyến mãi · Homi Admin')
@section('page_title', 'Khuyến mãi')
@section('page_subtitle', 'Quản lý mã giảm giá áp dụng khi khách đặt phòng.')

@section('content')
<div class="card" style="margin-bottom: 20px;">
    <div class="section-kicker">Từ nhân viên</div>
    <h2 class="section-title" style="font-size: 18px;">Đề xuất ưu đãi đoàn cho khách quen</h2>
    <p class="section-desc">Nhân viên gửi mã + % giảm giá đề xuất cho khách quen — duyệt sẽ tự động tạo khuyến mãi đang chạy, không giới hạn ngày.</p>

    @if ($promotionRequests->isEmpty())
        <div class="empty-box">Chưa có đề xuất nào từ nhân viên.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>% đề xuất</th>
                        <th>Nhân viên</th>
                        <th>Ghi chú</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($promotionRequests as $promoRequest)
                        <tr>
                            <td><span class="badge badge-blue">{{ $promoRequest->code }}</span></td>
                            <td>{{ (float) $promoRequest->discount_percent }}%</td>
                            <td>{{ $promoRequest->user->name ?? '—' }}</td>
                            <td>{{ $promoRequest->reason ?? '—' }}</td>
                            <td>
                                @php
                                    $promoStatusBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$promoRequest->status] ?? 'badge-green';
                                    $promoStatusLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$promoRequest->status] ?? $promoRequest->status;
                                @endphp
                                <span class="badge {{ $promoStatusBadge }}">{{ $promoStatusLabel }}</span>
                            </td>
                            <td>
                                @if ($promoRequest->isPending())
                                    <div class="action-row">
                                        <form method="POST" action="{{ route('admin.promotion-requests.approve', $promoRequest->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">Đồng ý</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.promotion-requests.reject', $promoRequest->id) }}" onsubmit="return confirm('Từ chối đề xuất mã này?');">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm">Từ chối</button>
                                        </form>
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 16px;">{{ $promotionRequests->links() }}</div>
    @endif
</div>

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
