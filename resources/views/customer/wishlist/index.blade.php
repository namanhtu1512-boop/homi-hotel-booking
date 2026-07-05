@extends('layouts.app')

@section('title', 'Yêu thích · Homi')
@section('banner_tag', 'Yêu thích')
@section('banner_title', 'Danh sách yêu thích của tôi')
@section('banner_subtitle', 'Gom các loại phòng bạn quan tâm, chọn ngày rồi tiến hành đặt phòng khi đã sẵn sàng.')

@section('content')
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
    <div class="card">
        <div class="empty-box">Danh sách yêu thích đang trống. <a href="{{ route('rooms.index') }}" class="font-semibold text-primary">Xem danh sách phòng</a> để thêm loại phòng bạn quan tâm.</div>
    </div>
@else
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($items as $item)
            @php $cover = $item->roomType?->images->first(); @endphp
            <div class="card !p-0 overflow-hidden">
                <div class="h-36 bg-primary-light/50 dark:bg-primary/10">
                    @if ($cover)
                        <img src="{{ $cover->image_url }}" alt="" class="h-full w-full object-cover">
                    @endif
                </div>
                <div class="space-y-3 p-4">
                    <div>
                        <div class="font-bold text-slate-900 dark:text-white">{{ $item->roomType->name ?? '—' }}</div>
                        <div class="text-sm font-bold text-primary">{{ number_format($item->roomType->price_per_night ?? 0, 0, ',', '.') }}đ/đêm</div>
                    </div>

                    <form method="POST" action="{{ route('customer.wishlist.update', $item->id) }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        @method('PATCH')
                        <label class="text-xs text-slate-500 dark:text-slate-400">SL<input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="10" class="input mt-1 w-14"></label>
                        <label class="text-xs text-slate-500 dark:text-slate-400">NL<input type="number" name="adults" value="{{ $item->adults }}" min="1" max="50" class="input mt-1 w-14"></label>
                        <label class="text-xs text-slate-500 dark:text-slate-400">TE<input type="number" name="children" value="{{ $item->children }}" min="0" max="50" class="input mt-1 w-14"></label>
                        <button type="submit" class="btn-outline btn-sm">Cập nhật</button>
                    </form>

                    <form method="POST" action="{{ route('customer.wishlist.destroy', $item->id) }}"
                        onsubmit="return confirm('Xóa &quot;{{ $item->roomType->name }}&quot; khỏi danh sách yêu thích?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger btn-sm w-full">Xóa</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mt-5 border-2 border-dashed border-primary/30">
        <span class="section-kicker">Tiến hành đặt phòng</span>
        <h3 class="mb-3 text-lg font-bold text-slate-900 dark:text-white">Chọn ngày lưu trú chung cho các phòng đã chọn</h3>

        <form method="GET" action="{{ route('customer.bookings.create') }}" class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="wishlist-check-in">Ngày nhận phòng</label>
                    <input class="input" type="date" id="wishlist-check-in" name="check_in" min="{{ now()->format('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label" for="wishlist-check-out">Ngày trả phòng</label>
                    <input class="input" type="date" id="wishlist-check-out" name="check_out" min="{{ now()->addDay()->format('Y-m-d') }}" required>
                </div>
            </div>

            @foreach ($items as $i => $item)
                <input type="hidden" name="items[{{ $i }}][room_type_id]" value="{{ $item->room_type_id }}">
                <input type="hidden" name="items[{{ $i }}][quantity]" value="{{ $item->quantity }}">
                <input type="hidden" name="items[{{ $i }}][adults]" value="{{ $item->adults }}">
                <input type="hidden" name="items[{{ $i }}][children]" value="{{ $item->children }}">
            @endforeach

            <button type="submit" class="btn-primary w-full">Tiến hành đặt phòng →</button>
        </form>
    </div>
@endif
@endsection
