@extends('layouts.app')

@section('title', 'Khuyến mãi · Homi')
@section('banner_tag', 'Ưu đãi')
@section('banner_title', 'Khuyến mãi đang diễn ra')
@section('banner_subtitle', 'Áp dụng mã khuyến mãi ngay ở bước đặt phòng để nhận ưu đãi.')

@section('content')
<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($promotions as $promo)
        <div class="card flex flex-col gap-3 border-2 border-dashed border-accent/40">
            <div class="flex items-center justify-between">
                <span class="badge badge-orange">{{ $promo->code }}</span>
                @if ($promo->ends_at)
                    <span class="text-xs font-semibold text-slate-400">HSD: {{ $promo->ends_at->format('d/m/Y') }}</span>
                @endif
            </div>
            <h3 class="font-heading text-lg font-bold text-slate-900 dark:text-white">{{ $promo->name }}</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $promo->description ?: 'Nhập mã khi đặt phòng để được giảm giá.' }}</p>
            <div class="mt-auto text-2xl font-extrabold text-accent">
                @if ($promo->discount_percent)
                    Giảm {{ (float) $promo->discount_percent }}%
                @elseif ($promo->discount_amount)
                    Giảm {{ number_format($promo->discount_amount, 0, ',', '.') }}đ
                @endif
            </div>
        </div>
    @empty
        <div class="empty-box sm:col-span-2 lg:col-span-3">Hiện chưa có khuyến mãi nào đang diễn ra.</div>
    @endforelse
</div>
@endsection
