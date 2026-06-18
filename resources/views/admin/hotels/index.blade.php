@extends('layouts.app')

@section('title', 'Quản lý khách sạn · Homi')
@section('banner_tag', 'Admin · Hotels')
@section('banner_title', 'Quản lý khách sạn')
@section('banner_subtitle', 'Thêm, sửa, xóa mềm, khôi phục và ẩn/hiện khách sạn trực tiếp trên giao diện web.')

@section('content')
<style>
    .hotel-admin-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 20px;
        margin-top: 8px;
    }

    .hotel-admin-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        overflow: hidden;
        box-shadow: var(--shadow-light);
        display: flex;
        flex-direction: column;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .hotel-admin-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow);
    }

    .hotel-admin-image {
        position: relative;
        display: block;
        height: 170px;
        background:
            linear-gradient(135deg, rgba(30, 94, 255, 0.25), rgba(255, 255, 255, 0.08)),
            url("https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=900&q=80");
        background-size: cover;
        background-position: center;
    }

    .hotel-admin-card:nth-child(2n) .hotel-admin-image {
        background:
            linear-gradient(135deg, rgba(30, 94, 255, 0.25), rgba(255, 255, 255, 0.08)),
            url("https://images.unsplash.com/photo-1564501049412-61c2a3083791?auto=format&fit=crop&w=900&q=80");
        background-size: cover;
        background-position: center;
    }

    .hotel-admin-card:nth-child(3n) .hotel-admin-image {
        background:
            linear-gradient(135deg, rgba(30, 94, 255, 0.25), rgba(255, 255, 255, 0.08)),
            url("https://images.unsplash.com/photo-1582719508461-905c673771fd?auto=format&fit=crop&w=900&q=80");
        background-size: cover;
        background-position: center;
    }

    .hotel-admin-image .real-photo {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .hotel-admin-stars {
        position: absolute;
        top: 12px;
        left: 12px;
        background: rgba(13, 32, 74, 0.72);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        padding: 5px 10px;
        border-radius: 999px;
    }

    .hotel-admin-status {
        position: absolute;
        top: 12px;
        right: 12px;
    }

    .hotel-admin-body {
        padding: 16px 18px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        flex-grow: 1;
    }

    .hotel-admin-city {
        font-size: 12px;
        font-weight: 700;
        color: var(--primary);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .hotel-admin-name {
        margin: 2px 0 4px;
        font-size: 17px;
        font-weight: 800;
        color: var(--text);
    }

    .hotel-admin-name a {
        color: inherit;
    }

    .hotel-admin-name a:hover {
        color: var(--primary);
    }

    .hotel-admin-address {
        font-size: 13px;
        color: var(--muted);
        line-height: 1.5;
        margin-bottom: 8px;
    }

    .hotel-admin-meta {
        font-size: 13px;
        color: var(--muted);
        margin-bottom: 10px;
    }

    .hotel-admin-actions {
        margin-top: auto;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding-top: 10px;
        border-top: 1px solid #eef3ff;
    }

    .hotel-admin-actions form {
        margin: 0;
    }
</style>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">Danh sách</div>
            <h2 class="section-title" style="margin-bottom: 6px;">{{ $hotels->total() }} khách sạn</h2>
            <p class="section-desc">Bấm vào ảnh hoặc tên khách sạn để xem chi tiết. Tìm kiếm, lọc trạng thái hoặc thêm khách sạn mới.</p>
        </div>

        <a href="{{ route('admin.hotels.create') }}" class="btn btn-primary">+ Thêm khách sạn</a>
    </div>

    <form method="GET" action="{{ route('admin.hotels.index') }}" class="filter-bar">
        <input type="text" name="search" value="{{ $search }}" placeholder="Tìm theo tên hoặc thành phố...">

        <select name="status">
            <option value="" @selected($status === '')>Tất cả trạng thái</option>
            <option value="active" @selected($status === 'active')>Đang hoạt động</option>
            <option value="hidden" @selected($status === 'hidden')>Đang ẩn</option>
            <option value="deleted" @selected($status === 'deleted')>Đã xóa mềm</option>
        </select>

        <button type="submit" class="btn btn-outline">Lọc</button>

        @if ($search || $status)
            <a href="{{ route('admin.hotels.index') }}" class="btn btn-light" style="color: var(--primary); border-color: var(--border);">Xóa lọc</a>
        @endif
    </form>

    @if ($hotels->isEmpty())
        <div class="empty-box">Không tìm thấy khách sạn nào.</div>
    @else
        <div class="hotel-admin-grid">
            @foreach ($hotels as $hotel)
                <article class="hotel-admin-card">
                    <a href="{{ route('admin.hotels.show', $hotel->id) }}" class="hotel-admin-image">
                        @if ($hotel->images->isNotEmpty())
                            <img class="real-photo" src="{{ $hotel->images->first()->path }}" alt="{{ $hotel->name }}">
                        @endif

                        @if ($hotel->star_rating)
                            <span class="hotel-admin-stars">{{ $hotel->star_rating }} sao</span>
                        @endif

                        <span class="hotel-admin-status">
                            @if ($hotel->trashed())
                                <span class="badge badge-red">Đã xóa mềm</span>
                            @elseif ($hotel->status === 'active')
                                <span class="badge badge-green">Đang hoạt động</span>
                            @else
                                <span class="badge badge-orange">Đang ẩn</span>
                            @endif
                        </span>
                    </a>

                    <div class="hotel-admin-body">
                        <div class="hotel-admin-city">{{ $hotel->city }}@if($hotel->district) · {{ $hotel->district }} @endif</div>
                        <h3 class="hotel-admin-name">
                            <a href="{{ route('admin.hotels.show', $hotel->id) }}">{{ $hotel->name }}</a>
                        </h3>
                        <div class="hotel-admin-address">{{ $hotel->address }}</div>
                        <div class="hotel-admin-meta">{{ $hotel->room_types_count }} loại phòng</div>

                        <div class="hotel-admin-actions">
                            @if ($hotel->trashed())
                                <form method="POST" action="{{ route('admin.hotels.restore', $hotel->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline btn-sm">Khôi phục</button>
                                </form>
                            @else
                                <a href="{{ route('admin.hotels.show', $hotel->id) }}" class="btn btn-outline btn-sm">Xem chi tiết</a>
                                <a href="{{ route('admin.hotels.edit', $hotel->id) }}" class="btn btn-outline btn-sm">Sửa</a>

                                <form method="POST" action="{{ route('admin.hotels.toggle-status', $hotel->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline btn-sm">
                                        {{ $hotel->status === 'active' ? 'Ẩn' : 'Hiện' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.hotels.destroy', $hotel->id) }}"
                                    onsubmit="return confirm('Xóa mềm khách sạn &quot;{{ $hotel->name }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="action-row" style="margin-top: 18px;">
            {{ $hotels->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection
