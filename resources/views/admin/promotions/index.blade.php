@extends('layouts.admin')

@section('title', 'Quản lý khuyến mãi · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div><h1>🏷️ Quản lý khuyến mãi</h1><p>Quản lý các chương trình khuyến mãi và thông báo hiển thị cho khách hàng</p></div>
        <div class="admin-page-actions"><a href="{{ route('admin.promotions.create') }}" class="btn btn-primary">➕ Thêm khuyến mãi</a></div>
    </div>

    <div class="data-card">
        <div class="data-card-header">Danh sách khuyến mãi <span class="count-pill">{{ $promotions->total() }}</span></div>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Tên</th><th>Loại</th><th>Áp dụng</th><th>Thời gian hiệu lực</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                    @forelse ($promotions as $promo)
                        <tr>
                            <td><div class="entity-name">{{ $promo->name }}</div>@if($promo->description)<div class="entity-sub">{{ \Illuminate\Support\Str::limit($promo->description, 60) }}</div>@endif</td>
                            <td><span class="badge {{ $promo->type === 'promotion' ? 'badge-blue' : 'badge-pending' }}">{{ $promo->type === 'promotion' ? 'Khuyến mãi' : 'Thông báo' }}</span></td>
                            <td>{{ $promo->hotel->name ?? 'Toàn hệ thống' }}</td>
                            <td>
                                @if ($promo->valid_from || $promo->valid_to)
                                    {{ $promo->valid_from?->format('d/m/Y') ?? '...' }} → {{ $promo->valid_to?->format('d/m/Y') ?? '...' }}
                                @else
                                    <span class="text-muted">Không giới hạn</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $promo->status === 'active' ? 'badge-confirmed' : 'badge-cancelled' }}">{{ $promo->status === 'active' ? 'Hoạt động' : 'Đã tắt' }}</span>
                                @if ($promo->status === 'active' && ! $promo->isCurrentlyValid())
                                    <span class="badge badge-pending" title="Đang hoạt động nhưng ngoài khoảng thời gian hiệu lực">Hết hạn</span>
                                @endif
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a class="icon-action" title="Sửa" href="{{ route('admin.promotions.edit', $promo->id) }}">✏️</a>
                                    <form method="POST" action="{{ route('admin.promotions.toggle-status', $promo->id) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="icon-action {{ $promo->status === 'active' ? 'danger' : 'success' }}" title="{{ $promo->status === 'active' ? 'Tắt' : 'Bật' }}">{{ $promo->status === 'active' ? '🚫' : '✔️' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><div class="icon">🏷️</div><h3>Chưa có khuyến mãi nào</h3><p>Bấm "Thêm khuyến mãi" để tạo chương trình đầu tiên</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials._pagination', ['paginator' => $promotions])
    </div>
@endsection
