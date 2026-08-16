@extends('layouts.app')

@section('title', 'Viết đánh giá · Homi')
@section('banner_tag', 'Đánh giá trải nghiệm')
@section('banner_title', 'Chia sẻ trải nghiệm của bạn')
@section('banner_subtitle', 'Đánh giá của bạn giúp những khách hàng khác lựa chọn tốt hơn.')

@section('content')
<div class="card">
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="mb-5 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
        <div class="font-bold text-slate-900 dark:text-white">{{ $booking->booking_code }}</div>
        <div class="text-sm text-slate-500 dark:text-slate-400">{{ $booking->bookingItems->pluck('roomType.name')->filter()->implode(', ') }}</div>
        <div class="text-xs text-slate-400">{{ $booking->check_in->format('d/m/Y') }} – {{ $booking->check_out->format('d/m/Y') }}</div>
    </div>

    <form method="POST" action="{{ route('customer.reviews.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <input type="hidden" name="booking_id" value="{{ $booking->id }}">

        <div>
            <label class="form-label">Đánh giá khách sạn *</label>
            {{-- Sao xếp NGƯỢC trong DOM (5→1) + flex-row-reverse để hiển thị
            đúng thứ tự 1→5 trái sang phải, nhưng cho phép chọn selector
            peer-checked (chỉ "nhìn" được sibling PHÍA SAU trong DOM) tô
            sáng ĐỦ các sao từ 1 tới sao đã chọn — không chỉ 1 sao lẻ. --}}
            <div class="flex flex-row-reverse gap-1 text-3xl">
                @for ($i = 5; $i >= 1; $i--)
                    <input type="radio" id="rating_{{ $i }}" name="rating" value="{{ $i }}"
                        class="peer hidden" @checked(old('rating') == $i) required>
                    <label for="rating_{{ $i }}"
                        class="peer-checked:text-accent cursor-pointer text-slate-300 transition-colors hover:text-accent">★</label>
                @endfor
            </div>
        </div>

        <div>
            <label class="form-label" for="comment">Bình luận</label>
            <textarea id="comment" name="comment" rows="4" class="input" placeholder="Cảm nhận của bạn về phòng, dịch vụ...">{{ old('comment') }}</textarea>
        </div>

        <div>
            <label class="form-label" for="images">Đăng ảnh (tối đa 5 ảnh)</label>
            <input id="images" type="file" name="images[]" accept="image/*" multiple class="input">
        </div>

        <button type="submit" class="btn-primary w-full">Gửi đánh giá</button>
    </form>
</div>
@endsection
