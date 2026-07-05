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

            <div>
                <label class="form-label">Số sao *</label>
                <div class="flex gap-2 text-3xl">
                    @for ($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer text-slate-300 has-[:checked]:text-accent">
                            <input type="radio" name="rating" value="{{ $i }}" class="hidden" @checked(old('rating') == $i) required>
                            ★
                        </label>
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
    @endif
</div>
@endsection
