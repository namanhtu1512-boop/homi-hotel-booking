@extends('layouts.app')

@section('title', 'Liên hệ · Homi')
@section('banner_tag', 'Liên hệ')
@section('banner_title', 'Liên hệ với chúng tôi')
@section('banner_subtitle', 'Có câu hỏi hoặc yêu cầu đặc biệt? Gửi tin nhắn cho Homi, chúng tôi sẽ phản hồi sớm nhất.')

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

        <form method="POST" action="{{ route('contact.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="form-label" for="name">Họ tên *</label>
                <input class="input" type="text" id="name" name="name" value="{{ old('name') }}" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="email">Email *</label>
                    <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div>
                    <label class="form-label" for="phone">Số điện thoại</label>
                    <input class="input" type="text" id="phone" name="phone" value="{{ old('phone') }}">
                </div>
            </div>

            <div>
                <label class="form-label" for="message">Nội dung *</label>
                <textarea class="input" id="message" name="message" rows="5" required>{{ old('message') }}</textarea>
            </div>

            <button type="submit" class="btn-primary w-full">Gửi liên hệ</button>
        </form>
    </div>

    <div class="card h-fit">
        <span class="section-kicker">Thông tin liên hệ</span>
        <div class="info-list mt-2.5">
            <div class="info-item">
                <span class="label">Địa chỉ</span>
                <span class="value">{{ $hotel->address }}</span>
            </div>
            @if ($hotel->phone)
                <div class="info-item">
                    <span class="label">Điện thoại</span>
                    <span class="value">{{ $hotel->phone }}</span>
                </div>
            @endif
            @if ($hotel->email)
                <div class="info-item">
                    <span class="label">Email</span>
                    <span class="value">{{ $hotel->email }}</span>
                </div>
            @endif
        </div>

        <div class="mt-4">
            @include('partials._map-embed', ['hotel' => $hotel])
        </div>
    </div>
</div>
@endsection
