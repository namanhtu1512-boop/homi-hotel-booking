@extends('layouts.admin')

@section('title', $hotel->name . ' · Homi Admin')

@push('styles')
<style>
    .hotel-gallery { display: grid; grid-template-columns: 2fr 1fr; gap: .75rem; margin-bottom: 1.25rem; }
    .hotel-gallery-main { height: 320px; border-radius: var(--radius-lg); overflow: hidden; background: linear-gradient(135deg, var(--blue-mid), var(--blue-light)); display: flex; align-items: center; justify-content: center; font-size: 3rem; }
    .hotel-gallery-main img { width: 100%; height: 100%; object-fit: cover; }
    .hotel-gallery-side { display: grid; grid-template-rows: 1fr 1fr; gap: .75rem; }
    .hotel-gallery-side .thumb { border-radius: var(--radius-lg); overflow: hidden; background: linear-gradient(135deg, var(--blue-mid), var(--blue-light)); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .hotel-gallery-side .thumb img { width: 100%; height: 100%; object-fit: cover; }
    .amenity-tags { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .5rem; }
    .amenity-tag { background: var(--blue-light); color: var(--blue-dark); font-size: .82rem; font-weight: 600; padding: .4rem .8rem; border-radius: 999px; }
    @media (max-width: 768px) { .hotel-gallery { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>🏨 {{ $hotel->name }}</h1>
            <p>
                {{ $hotel->city }}@if($hotel->district) · {{ $hotel->district }}@endif
                @if ($hotel->star_rating) · <span class="stars">{{ str_repeat('★', $hotel->star_rating) }}</span> @endif
                @if ($hotel->trashed())
                    <span class="badge badge-cancelled">Đã xóa mềm</span>
                @elseif ($hotel->status === 'active')
                    <span class="badge badge-confirmed">Đang hoạt động</span>
                @else
                    <span class="badge badge-pending">Đang ẩn</span>
                @endif
            </p>
        </div>
        <div class="admin-page-actions"><a href="{{ route('admin.hotels.index') }}" class="btn btn-outline">← Quay lại danh sách</a></div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="hotel-gallery">
                <div class="hotel-gallery-main">
                    @if ($hotel->images->isNotEmpty())
                        <img src="{{ $hotel->images->first()->path }}" alt="{{ $hotel->name }}">
                    @else
                        🏨
                    @endif
                </div>
                <div class="hotel-gallery-side">
                    <div class="thumb">@if ($hotel->images->count() > 1)<img src="{{ $hotel->images[1]->path }}" alt="">@endif</div>
                    <div class="thumb">@if ($hotel->images->count() > 2)<img src="{{ $hotel->images[2]->path }}" alt="">@endif</div>
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <h3 style="font-size:1rem;font-weight:700;margin-bottom:.5rem">Mô tả</h3>
                    <p class="text-muted">{{ $hotel->description ?: 'Chưa có mô tả cho khách sạn này.' }}</p>

                    <h3 style="font-size:1rem;font-weight:700;margin:1.25rem 0 .5rem">Tiện ích</h3>
                    @if ($hotel->amenities->isEmpty())
                        <p class="text-muted">Chưa gán tiện ích nào.</p>
                    @else
                        <div class="amenity-tags">
                            @foreach ($hotel->amenities as $amenity)
                                <span class="amenity-tag">{{ $amenity->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <div class="info-grid">
                        <div class="info-item"><label>Địa chỉ</label><p>{{ $hotel->address }}</p></div>
                        <div class="info-item"><label>Số loại phòng</label><p>{{ $hotel->room_types_count }}</p></div>
                        <div class="info-item"><label>Số ảnh</label><p>{{ $hotel->images->count() }}</p></div>
                        <div class="info-item"><label>Ngày tạo</label><p>{{ $hotel->created_at->format('d/m/Y H:i') }}</p></div>
                    </div>

                    <div class="action-row" style="margin-top:1.25rem;flex-direction:column">
                        @if ($hotel->trashed())
                            @if (auth()->user()->role === 'admin')
                                <form method="POST" action="{{ route('admin.hotels.restore', $hotel->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-block">Khôi phục khách sạn</button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('admin.hotels.edit', $hotel->id) }}" class="btn btn-primary btn-block">Sửa thông tin</a>

                            <form method="POST" action="{{ route('admin.hotels.toggle-status', $hotel->id) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-outline btn-block">{{ $hotel->status === 'active' ? 'Ẩn khách sạn' : 'Hiện khách sạn' }}</button>
                            </form>

                            @if (auth()->user()->role === 'admin')
                                <form method="POST" action="{{ route('admin.hotels.destroy', $hotel->id) }}" onsubmit="return confirm('Xóa mềm khách sạn &quot;{{ $hotel->name }}&quot;?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-block">Xóa mềm khách sạn</button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
