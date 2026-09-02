@extends('layouts.staff')

@section('title', $roomType->name . ' · Homi Nhân viên')
@section('page_title', $roomType->name)
@section('page_subtitle', 'Chi tiết loại phòng')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            @if ($roomType->status === 'active')
                <span class="badge badge-green">Đang hoạt động</span>
            @elseif ($roomType->status === 'hidden')
                <span class="badge badge-orange">Đang ẩn</span>
            @else
                <span class="badge badge-red">Bảo trì</span>
            @endif
        </div>

        <div class="action-row">
            <a href="{{ route('staff.room-types.index') }}" class="btn btn-outline">Quay lại danh sách</a>
        </div>
    </div>

    <div class="card overflow-hidden !p-0" style="margin-bottom: 20px;">
        @include('partials._room-gallery', ['images' => $roomType->images, 'alt' => $roomType->name])
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <div class="section-kicker">Mô tả</div>
            <p class="section-desc">{{ $roomType->description ?: 'Chưa có mô tả.' }}</p>
        </div>

        <div>
            <div class="section-kicker">Thông tin phòng</div>
            <p class="section-desc">
                Giá / đêm: {{ number_format($roomType->price_per_night, 0, ',', '.') }}đ<br>
                Sức chứa: {{ $roomType->capacity }} khách<br>
                Loại giường: {{ $roomType->bed_type ?: 'Chưa cập nhật' }}<br>
                Diện tích: {{ $roomType->area ? $roomType->area . ' m²' : 'Chưa cập nhật' }}<br>
                Tổng số phòng: {{ $roomType->total_rooms }}
            </p>
        </div>
    </div>

    <div style="margin-top: 20px;">
        <div class="section-kicker">Chính sách giường phụ</div>
        <p class="section-desc">
            @if (! $roomType->supportsExtraBed())
                Nhóm phòng này ({{ $roomType->category ?: 'chưa phân nhóm' }}) không hỗ trợ giường phụ.
            @elseif ($roomType->category === 'family')
                Được thêm tối đa 1 giường phụ cho trẻ em, nằm ngoài giới hạn 2 trẻ em cơ bản/phòng (không tính vào sức chứa người lớn).
            @else
                Được thêm giường phụ cho khách vượt quá sức chứa cơ bản ({{ $roomType->capacity }} khách/phòng).
            @endif
        </p>
    </div>
</div>

@include('partials._room-amenities', ['roomType' => $roomType, 'amenityTiers' => $amenityTiers])

<div class="card">
    <div class="section-kicker">Đánh giá</div>
    <h3 class="mb-3 text-lg font-bold text-slate-900 dark:text-white">
        @if ($reviewSummary['count'] > 0)
            ★ {{ $reviewSummary['avg'] }}/5 ({{ $reviewSummary['count'] }} đánh giá)
        @else
            Chưa có đánh giá
        @endif
    </h3>

    @if ($reviews->isEmpty())
        <p class="section-desc">Chưa có khách nào đánh giá loại phòng này.</p>
    @else
        <div class="space-y-4">
            @foreach ($reviews as $review)
                <div class="border-t border-slate-200 pt-4 first:border-0 first:pt-0 dark:border-slate-800">
                    <div class="text-accent">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $review->comment }}</p>
                    <div class="mt-1 text-xs font-semibold text-slate-400">{{ $review->user->name ?? 'Khách Homi' }} · {{ $review->created_at->format('d/m/Y') }}</div>
                    @if (! empty($review->images))
                        <div class="mt-2 flex gap-2">
                            @foreach ($review->images as $img)
                                <img src="{{ asset('storage/' . $img) }}" class="h-16 w-16 rounded-lg object-cover" alt="">
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
