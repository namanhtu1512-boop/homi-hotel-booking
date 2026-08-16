@extends('layouts.admin')

@section('title', 'Đánh giá · Homi Admin')
@section('page_title', 'Đánh giá khách hàng')
@section('page_subtitle', 'Ẩn/xóa các đánh giá vi phạm.')

@section('content')
<div class="card">
    <form method="GET" class="filter-bar">
        <select name="status" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            <option value="visible" @selected(($filters['status'] ?? '') === 'visible')>Đang hiển thị</option>
            <option value="hidden" @selected(($filters['status'] ?? '') === 'hidden')>Đã ẩn</option>
        </select>
    </form>

    @if ($reviews->isEmpty())
        <div class="empty-box">Chưa có đánh giá nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Khách</th>
                        <th>Loại phòng</th>
                        <th>Số sao</th>
                        <th>Bình luận</th>
                        <th>Ảnh</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reviews as $review)
                        <tr>
                            <td>{{ $review->user->name ?? '—' }}</td>
                            <td>{{ $review->roomType->name ?? '—' }}</td>
                            <td>{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</td>
                            <td style="max-width: 320px;">{{ $review->comment ?: '—' }}</td>
                            <td>
                                @if (! empty($review->images))
                                    <div class="action-row">
                                        @foreach ($review->images as $img)
                                            <a href="{{ asset('storage/' . $img) }}" target="_blank" rel="noopener">
                                                <img src="{{ asset('storage/' . $img) }}" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px;">
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="badge {{ $review->status === 'visible' ? 'badge-green' : 'badge-orange' }}">{{ $review->status === 'visible' ? 'Hiển thị' : 'Đã ẩn' }}</span></td>
                            <td>
                                <div class="action-row">
                                    <form method="POST" action="{{ route('admin.reviews.toggle', $review->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline btn-sm">{{ $review->status === 'visible' ? 'Ẩn' : 'Hiện lại' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.reviews.destroy', $review->id) }}" onsubmit="return confirm('Xóa đánh giá này?');">
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
        <div style="margin-top: 16px;">{{ $reviews->links() }}</div>
    @endif
</div>
@endsection
