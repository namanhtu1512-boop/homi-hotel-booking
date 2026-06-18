@extends('layouts.app')

@section('title', $hotel->name . ' · Homi')
@section('banner_tag', 'Admin · Hotels')
@section('banner_title', $hotel->name)
@section('banner_subtitle', $hotel->address)

@section('content')
<style>
    .detail-gallery {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 12px;
        margin-bottom: 24px;
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .detail-gallery-main {
        height: 340px;
        background:
            linear-gradient(135deg, rgba(30, 94, 255, 0.25), rgba(255, 255, 255, 0.08)),
            url("https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1200&q=80");
        background-size: cover;
        background-position: center;
    }

    .detail-gallery-main img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .detail-gallery-side {
        display: grid;
        grid-template-rows: 1fr 1fr;
        gap: 12px;
    }

    .detail-gallery-side .thumb {
        height: 100%;
        min-height: 100px;
        background:
            linear-gradient(135deg, rgba(30, 94, 255, 0.25), rgba(255, 255, 255, 0.08)),
            url("https://images.unsplash.com/photo-1564501049412-61c2a3083791?auto=format&fit=crop&w=600&q=80");
        background-size: cover;
        background-position: center;
    }

    .detail-gallery-side .thumb:nth-child(2) {
        background:
            linear-gradient(135deg, rgba(30, 94, 255, 0.25), rgba(255, 255, 255, 0.08)),
            url("https://images.unsplash.com/photo-1582719508461-905c673771fd?auto=format&fit=crop&w=600&q=80");
        background-size: cover;
        background-position: center;
    }

    .detail-gallery-side .thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    @media (max-width: 768px) {
        .detail-gallery {
            grid-template-columns: 1fr;
        }
    }

    .amenity-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    .amenity-tag {
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 13px;
        font-weight: 600;
        padding: 7px 12px;
        border-radius: 999px;
    }
</style>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">{{ $hotel->city }}@if($hotel->district) · {{ $hotel->district }} @endif</div>
            <h2 class="section-title" style="margin-bottom: 6px;">{{ $hotel->name }}</h2>
            <p class="section-desc">
                @if ($hotel->star_rating)
                    <span class="badge badge-blue">{{ $hotel->star_rating }} sao</span>
                @endif
                @if ($hotel->trashed())
                    <span class="badge badge-red">Đã xóa mềm</span>
                @elseif ($hotel->status === 'active')
                    <span class="badge badge-green">Đang hoạt động</span>
                @else
                    <span class="badge badge-orange">Đang ẩn</span>
                @endif
            </p>
        </div>

        <a href="{{ route('admin.hotels.index') }}" class="btn btn-outline">Quay lại danh sách</a>
    </div>

    <div class="detail-gallery">
        <div class="detail-gallery-main">
            @if ($hotel->images->isNotEmpty())
                <img src="{{ $hotel->images->first()->path }}" alt="{{ $hotel->name }}">
            @endif
        </div>

        <div class="detail-gallery-side">
            <div class="thumb">
                @if ($hotel->images->count() > 1)
                    <img src="{{ $hotel->images[1]->path }}" alt="{{ $hotel->name }}">
                @endif
            </div>
            <div class="thumb">
                @if ($hotel->images->count() > 2)
                    <img src="{{ $hotel->images[2]->path }}" alt="{{ $hotel->name }}">
                @endif
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div>
            <div class="section-kicker">Thông tin</div>
            <h2 class="section-title">Mô tả</h2>
            <p class="section-desc">{{ $hotel->description ?: 'Chưa có mô tả cho khách sạn này.' }}</p>

            <div class="section-kicker" style="margin-top: 22px;">Tiện ích</div>
            <h2 class="section-title">Dịch vụ đi kèm</h2>
            @if ($hotel->amenities->isEmpty())
                <p class="section-desc">Chưa gán tiện ích nào.</p>
            @else
                <div class="amenity-tags">
                    @foreach ($hotel->amenities as $amenity)
                        <span class="amenity-tag">{{ $amenity->name }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <div class="info-list">
                <div class="info-item">
                    <span class="label">Địa chỉ</span>
                    <span class="value">{{ $hotel->address }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Số loại phòng</span>
                    <span class="value">{{ $hotel->room_types_count }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Số ảnh</span>
                    <span class="value">{{ $hotel->images->count() }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Ngày tạo</span>
                    <span class="value">{{ $hotel->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <div class="quick-actions">
                @if ($hotel->trashed())
                    <form method="POST" action="{{ route('admin.hotels.restore', $hotel->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-block">Khôi phục khách sạn</button>
                    </form>
                @else
                    <a href="{{ route('admin.hotels.edit', $hotel->id) }}" class="btn btn-primary btn-block">Sửa thông tin</a>

                    <form method="POST" action="{{ route('admin.hotels.toggle-status', $hotel->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline btn-block">
                            {{ $hotel->status === 'active' ? 'Ẩn khách sạn' : 'Hiện khách sạn' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.hotels.destroy', $hotel->id) }}"
                        onsubmit="return confirm('Xóa mềm khách sạn &quot;{{ $hotel->name }}&quot;?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block">Xóa mềm khách sạn</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
