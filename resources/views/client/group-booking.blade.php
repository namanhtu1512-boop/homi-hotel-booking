@extends('layouts.app')

@section('title', 'Đặt đoàn/nhóm · Homi')
@section('banner_tag', 'Đặt đoàn/nhóm')
@section('banner_title', 'Đặt phòng cho đoàn/nhóm')
@section('banner_subtitle', 'Từ 5 phòng trở lên? Gửi yêu cầu để Homi liên hệ báo giá ưu đãi riêng cho đoàn/công ty của bạn.')

@section('content')
<div class="grid gap-5 md:grid-cols-[1.3fr_0.7fr]">
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

        @guest
            <div class="alert alert-warning">
                Bạn cần <a href="{{ route('login') }}?redirect={{ urlencode(route('group-bookings.show')) }}" class="font-semibold underline">đăng nhập</a>
                hoặc <a href="{{ route('register') }}" class="font-semibold underline">đăng ký</a>
                để gửi yêu cầu đặt đoàn — nhờ đó Homi có thể liên hệ lại qua chat trực tiếp.
            </div>
        @endguest

        <form method="POST" action="{{ route('group-bookings.store') }}" class="space-y-4" @guest aria-disabled="true" @endguest>
            @csrf

            <div>
                <label class="form-label" for="company_name">Tên công ty/tổ chức (nếu có)</label>
                <input class="input" type="text" id="company_name" name="company_name" value="{{ old('company_name') }}">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="contact_name">Họ tên người liên hệ *</label>
                    <input class="input" type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', auth()->user()?->name) }}" required>
                </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="group_size">Số lượng khách *</label>
                    <input class="input" type="number" id="group_size" name="group_size" min="1" value="{{ old('group_size', 5) }}" required>
                </div>
                <div>
                    <label class="form-label" for="room_count">Số phòng *</label>
                    <input class="input" type="number" id="room_count" name="room_count" min="5" value="{{ old('room_count', 5) }}" required>
                </div>
            </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="email">Email *</label>
                    <input class="input" type="email" id="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required>
                </div>
                <div>
                    <label class="form-label" for="phone">Số điện thoại</label>
                    <input class="input" type="text" id="phone" name="phone" value="{{ old('phone') }}">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="check_in">Ngày nhận phòng dự kiến</label>
                    <input class="input" type="date" id="check_in" name="check_in" value="{{ old('check_in') }}">
                </div>
                <div>
                    <label class="form-label" for="check_out">Ngày trả phòng dự kiến</label>
                    <input class="input" type="date" id="check_out" name="check_out" value="{{ old('check_out') }}">
                </div>
            </div>

            @if ($roomTypes->isNotEmpty())
                <div>
                    <label class="form-label">Loại phòng quan tâm</label>
                    <div class="checkbox-grid">
                        @foreach ($roomTypes as $roomType)
                            <label class="checkbox-item">
                                <input type="checkbox" name="room_type_ids[]" value="{{ $roomType->id }}"
                                    @checked(in_array((string) $roomType->id, old('room_type_ids', [])))>
                                {{ $roomType->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <label class="form-label" for="message">Ghi chú thêm</label>
                <textarea class="input" id="message" name="message" rows="4" placeholder="Mục đích chuyến đi, ngân sách dự kiến, yêu cầu đặc biệt...">{{ old('message') }}</textarea>
            </div>

            <button type="submit" class="btn-primary w-full" @guest disabled @endguest>Gửi yêu cầu báo giá</button>
        </form>
    </div>

    <div class="h-fit space-y-5">
        <div class="card">
            <span class="section-kicker">Vì sao đặt đoàn qua form này?</span>
            <ul class="mt-3 list-disc space-y-2 pl-4 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                <li>Áp dụng cho đoàn/nhóm từ <strong>5 phòng trở lên</strong>.</li>
                <li>Homi sẽ liên hệ trực tiếp qua email/điện thoại để báo giá ưu đãi phù hợp quy mô đoàn.</li>
                <li>Không phải đặt phòng tự động — đây là yêu cầu tư vấn, chưa phát sinh chi phí.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
