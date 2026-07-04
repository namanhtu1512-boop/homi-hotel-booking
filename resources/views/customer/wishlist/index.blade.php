@extends('layouts.app')

@section('title', 'Danh sách chờ · Homi')
@section('banner_tag', 'Danh sách chờ')
@section('banner_title', 'Danh sách chờ của tôi')
@section('banner_subtitle', 'Gom các loại phòng bạn quan tâm, chọn ngày rồi tiến hành đặt phòng khi đã sẵn sàng.')

@section('content')
<div class="card">
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

    @if ($items->isEmpty())
        <div class="empty-box">Danh sách chờ đang trống. <a href="{{ route('rooms.index') }}">Xem danh sách phòng</a> để thêm loại phòng bạn quan tâm.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Loại phòng</th>
                        <th>Giá/đêm</th>
                        <th>Số lượng &amp; số khách</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        @php $cover = $item->roomType?->images->first(); @endphp
                        <tr>
                            <td>
                                @if ($cover)
                                    <img src="{{ $cover->image_url }}" alt="" style="width: 56px; height: 40px; object-fit: cover; border-radius: 6px;">
                                @else
                                    <span class="badge">Chưa có ảnh</span>
                                @endif
                            </td>
                            <td>{{ $item->roomType->name ?? '—' }}</td>
                            <td>{{ number_format($item->roomType->price_per_night ?? 0, 0, ',', '.') }}đ</td>
                            <td>
                                <form method="POST" action="{{ route('customer.wishlist.update', $item->id) }}"
                                      style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                    @csrf
                                    @method('PATCH')
                                    <label style="font-size:11px; color:var(--muted); display:flex; align-items:center; gap:4px;">
                                        SL <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="10" style="width:56px;">
                                    </label>
                                    <label style="font-size:11px; color:var(--muted); display:flex; align-items:center; gap:4px;">
                                        Người lớn <input type="number" name="adults" value="{{ $item->adults }}" min="1" max="50" style="width:56px;">
                                    </label>
                                    <label style="font-size:11px; color:var(--muted); display:flex; align-items:center; gap:4px;">
                                        Trẻ em <input type="number" name="children" value="{{ $item->children }}" min="0" max="50" style="width:56px;">
                                    </label>
                                    <button type="submit" class="btn btn-outline btn-sm">Cập nhật</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('customer.wishlist.destroy', $item->id) }}"
                                      onsubmit="return confirm('Xóa &quot;{{ $item->roomType->name }}&quot; khỏi danh sách chờ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card" style="margin-top: 20px; background: var(--primary-soft);">
            <div class="section-kicker">Tiến hành đặt phòng</div>
            <h3 class="section-title" style="font-size: 18px;">Chọn ngày lưu trú chung cho các phòng đã chọn</h3>

            <form method="GET" action="{{ route('customer.bookings.create') }}" class="form-grid" style="margin-top: 12px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label for="wishlist-check-in">Ngày nhận phòng</label>
                        <input type="date" id="wishlist-check-in" name="check_in" min="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="wishlist-check-out">Ngày trả phòng</label>
                        <input type="date" id="wishlist-check-out" name="check_out" min="{{ now()->addDay()->format('Y-m-d') }}" required>
                    </div>
                </div>

                @foreach ($items as $i => $item)
                    <input type="hidden" name="items[{{ $i }}][room_type_id]" value="{{ $item->room_type_id }}">
                    <input type="hidden" name="items[{{ $i }}][quantity]" value="{{ $item->quantity }}">
                    <input type="hidden" name="items[{{ $i }}][adults]" value="{{ $item->adults }}">
                    <input type="hidden" name="items[{{ $i }}][children]" value="{{ $item->children }}">
                @endforeach

                <button type="submit" class="btn btn-primary btn-block">Tiến hành đặt phòng →</button>
            </form>
        </div>
    @endif
</div>
@endsection
