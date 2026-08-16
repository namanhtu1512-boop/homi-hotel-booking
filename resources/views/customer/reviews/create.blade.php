@extends('layouts.app')

@section('title', 'Viết đánh giá · Homi')
@section('banner_tag', 'Đánh giá trải nghiệm')
@section('banner_title', 'Chia sẻ trải nghiệm của bạn')
@section('banner_subtitle', 'Đánh giá của bạn giúp những khách hàng khác lựa chọn tốt hơn.')

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
        <div class="empty-box">
            Bạn chưa có đơn đặt phòng nào đã hoàn tất để đánh giá, hoặc bạn đã đánh giá hết rồi.
            <a href="{{ route('customer.bookings.index') }}" class="text-primary font-semibold">Xem lịch sử đặt phòng</a>
        </div>
    @else
        <form method="POST" action="{{ route('customer.reviews.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label class="form-label" for="booking_item">Chọn đơn / loại phòng muốn đánh giá *</label>
                <select id="booking_item" class="input" onchange="
                        const [bookingId, roomTypeId] = this.value.split('|');
                        document.getElementById('booking_id').value = bookingId;
                        document.getElementById('room_type_id').value = roomTypeId;
                    " required>
                    <option value="">— Chọn —</option>
                    @foreach ($items as $item)
                        <option value="{{ $item['booking']->id }}|{{ $item['room_type']->id }}">
                            {{ $item['room_type']->name }} — đơn {{ $item['booking']->booking_code }} ({{ $item['booking']->check_in->format('d/m/Y') }})
                        </option>
                    @endforeach
                </select>
                <input type="hidden" id="booking_id" name="booking_id" value="{{ old('booking_id') }}">
                <input type="hidden" id="room_type_id" name="room_type_id" value="{{ old('room_type_id') }}">
            </div>

            @php
                $ratingCriteria = [
                    'rating'             => 'Đánh giá tổng thể',
                    'cleanliness_rating' => 'Vệ sinh phòng',
                    'service_rating'     => 'Thái độ phục vụ',
                    'value_rating'       => 'Giá cả hợp lý',
                ];
            @endphp

            @foreach ($ratingCriteria as $field => $label)
                <div>
                    <label class="form-label">{{ $label }} *</label>
                    {{-- Sao xếp NGƯỢC trong DOM (5→1) + flex-row-reverse để hiển thị
                    đúng thứ tự 1→5 trái sang phải, nhưng cho phép chọn selector
                    peer-checked (chỉ "nhìn" được sibling PHÍA SAU trong DOM) tô
                    sáng ĐỦ các sao từ 1 tới sao đã chọn — không chỉ 1 sao lẻ. --}}
                    <div class="flex flex-row-reverse gap-1 text-3xl">
                        @for ($i = 5; $i >= 1; $i--)
                            <input type="radio" id="{{ $field }}_{{ $i }}" name="{{ $field }}" value="{{ $i }}"
                                class="peer hidden" @checked(old($field) == $i) required>
                            <label for="{{ $field }}_{{ $i }}"
                                class="peer-checked:text-accent cursor-pointer text-slate-300 transition-colors hover:text-accent">★</label>
                        @endfor
                    </div>
                </div>
            @endforeach

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
    @endif
</div>
@endsection
