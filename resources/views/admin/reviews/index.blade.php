@extends('layouts.admin')

@section('title', 'Kiểm duyệt đánh giá · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div><h1>⭐ Kiểm duyệt đánh giá</h1><p>Xem và ẩn/hiện các đánh giá của khách hàng về khách sạn</p></div>
    </div>

    <form method="GET" action="{{ route('admin.reviews.index') }}" class="admin-toolbar">
        <select class="form-control" name="hotel_id" onchange="this.form.submit()">
            <option value="">Tất cả khách sạn</option>
            @foreach ($hotels as $hotel)
                <option value="{{ $hotel->id }}" @selected((string) $hotelId === (string) $hotel->id)>{{ $hotel->name }}</option>
            @endforeach
        </select>
        <select class="form-control" name="rating" onchange="this.form.submit()">
            <option value="">Tất cả số sao</option>
            @for ($i = 5; $i >= 1; $i--)
                <option value="{{ $i }}" @selected((string) $rating === (string) $i)>{{ $i }} sao</option>
            @endfor
        </select>
        <select class="form-control" name="visible" onchange="this.form.submit()">
            <option value="" @selected($visible === '')>Tất cả trạng thái</option>
            <option value="1" @selected($visible === '1')>Đang hiện</option>
            <option value="0" @selected($visible === '0')>Đã ẩn</option>
        </select>
        @if ($hotelId || $rating || $visible !== '')
            <a href="{{ route('admin.reviews.index') }}" class="btn btn-ghost">Xóa lọc</a>
        @endif
    </form>

    <div class="data-card">
        <div class="data-card-header">Danh sách đánh giá <span class="count-pill">{{ $reviews->total() }}</span></div>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Khách hàng</th><th>Khách sạn</th><th>Đánh giá</th><th>Nội dung</th><th>Ngày</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                    @forelse ($reviews as $review)
                        <tr>
                            <td>{{ $review->user->name ?? '—' }}</td>
                            <td>{{ $review->hotel->name ?? '—' }}</td>
                            <td><span class="stars">{{ str_repeat('★', $review->rating) }}</span><span class="stars-muted">{{ str_repeat('★', 5 - $review->rating) }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($review->comment, 80) ?: '—' }}</td>
                            <td>{{ $review->created_at->format('d/m/Y') }}</td>
                            <td><span class="badge {{ $review->is_visible ? 'badge-confirmed' : 'badge-cancelled' }}">{{ $review->is_visible ? 'Đang hiện' : 'Đã ẩn' }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('admin.reviews.toggle-visibility', $review) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="icon-action {{ $review->is_visible ? 'danger' : 'success' }}" title="{{ $review->is_visible ? 'Ẩn đánh giá' : 'Hiện lại' }}">{{ $review->is_visible ? '🙈' : '👁️' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state"><div class="icon">⭐</div><h3>Chưa có đánh giá nào</h3></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials._pagination', ['paginator' => $reviews])
    </div>
@endsection
