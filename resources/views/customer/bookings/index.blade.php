@extends('layouts.app')

@section('title', 'Đơn đặt phòng của tôi · Homi')
@section('banner_tag', 'Đơn của tôi')
@section('banner_title', 'Đơn đặt phòng của tôi')
@section('banner_subtitle', 'Theo dõi trạng thái và lịch sử đặt phòng của bạn.')

@section('content')
<div class="card">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('customer.bookings.index') }}" class="filter-bar">
        <select name="status" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            @foreach (\App\Enums\BookingStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>
    </form>

    @if ($bookings->isEmpty())
        <div class="empty-box">Bạn chưa có đơn đặt phòng nào. <a href="{{ route('rooms.index') }}" class="font-semibold text-primary">Xem danh sách phòng</a>.</div>
    @else
        <div class="space-y-3">
            @foreach ($bookings as $booking)
                @php
                    $cover = $booking->bookingItems->first()?->roomType?->images->first();
                    $canReview = $booking->status === \App\Enums\BookingStatus::COMPLETED;
                @endphp
                <div class="flex flex-wrap items-center gap-4 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <div class="h-16 w-24 shrink-0 overflow-hidden rounded-lg bg-primary-light/50 dark:bg-primary/10">
                        @if ($cover)
                            <img src="{{ $cover->image_url }}" alt="" class="h-full w-full object-cover">
                        @endif
                    </div>

                    <div class="min-w-[200px] flex-1">
                        <div class="font-bold text-slate-900 dark:text-white">{{ $booking->booking_code }}</div>
                        <div class="text-sm text-slate-500 dark:text-slate-400">{{ $booking->bookingItems->pluck('roomType.name')->filter()->implode(', ') }}</div>
                        <div class="text-xs text-slate-400">{{ $booking->check_in->format('d/m/Y') }} – {{ $booking->check_out->format('d/m/Y') }}</div>
                    </div>

                    <div class="text-right">
                        <div class="font-bold text-primary">{{ number_format($booking->total_amount, 0, ',', '.') }}đ</div>
                        <div class="mt-1 flex gap-1.5">
                            <span class="badge {{ $booking->status->badgeClass() }}">{{ $booking->status->label() }}</span>
                            @if ($booking->payment)
                                <span class="badge {{ $booking->payment->status->badgeClass() }}">{{ $booking->payment->status->label() }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex gap-2">
                        @if ($canReview)
                            <a href="{{ route('customer.reviews.create') }}" class="btn-outline btn-sm">Đánh giá</a>
                        @endif
                        <a href="{{ route('customer.bookings.show', $booking->id) }}" class="btn-outline btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5 flex items-center justify-between">
            @if ($bookings->onFirstPage())
                <span class="btn-outline btn-sm opacity-50">« Trước</span>
            @else
                <a href="{{ $bookings->previousPageUrl() }}" class="btn-outline btn-sm">« Trước</a>
            @endif

            <span class="text-sm text-slate-500 dark:text-slate-400">Trang {{ $bookings->currentPage() }}/{{ $bookings->lastPage() }}</span>

            @if ($bookings->hasMorePages())
                <a href="{{ $bookings->nextPageUrl() }}" class="btn-outline btn-sm">Sau »</a>
            @else
                <span class="btn-outline btn-sm opacity-50">Sau »</span>
            @endif
        </div>
    @endif
</div>
@endsection
