@extends('layouts.app')

@section('title', 'Khuyến mãi · Homi')
@section('banner_tag', 'Ưu đãi')
@section('banner_title', 'Khuyến mãi đang diễn ra')
@section('banner_subtitle', 'Áp dụng mã khuyến mãi ngay ở bước đặt phòng để nhận ưu đãi.')

@section('content')
<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($promotions as $promo)
        <div class="promo-card card flex flex-col gap-3 overflow-hidden border-0 bg-gradient-to-br from-accent/10 via-transparent to-transparent p-0 shadow-sm ring-1 ring-accent/20">
            <div class="flex items-center justify-between bg-accent-light/60 px-4 pt-4 pb-3 dark:bg-accent/10">
                <div class="text-2xl font-extrabold text-accent-dark dark:text-accent">
                    @if ($promo->discount_percent)
                        -{{ rtrim(rtrim(number_format((float) $promo->discount_percent, 1), '0'), '.') }}%
                    @elseif ($promo->discount_amount)
                        -{{ number_format($promo->discount_amount, 0, ',', '.') }}đ
                    @endif
                </div>
                @if ($promo->ends_at)
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">HSD: {{ $promo->ends_at->format('d/m/Y') }}</span>
                @endif
            </div>

            <div class="flex flex-1 flex-col gap-3 px-4 pb-4">
                <h3 class="font-heading text-lg font-bold text-slate-900 dark:text-white">{{ $promo->name }}</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $promo->description ?: 'Nhập mã khi đặt phòng để được giảm giá.' }}</p>

                <button type="button"
                    class="promo-copy-btn mt-auto flex w-full items-center justify-between gap-2 rounded-lg border-2 border-dashed border-accent/50 bg-white px-3 py-2 text-left transition hover:border-accent hover:bg-accent-light/40 dark:bg-slate-900 dark:hover:bg-accent/10"
                    data-code="{{ $promo->code }}" onclick="copyPromoCode(this)" title="Bấm để sao chép mã">
                    <span class="font-mono text-base font-bold tracking-wider text-slate-900 dark:text-white">{{ $promo->code }}</span>
                    <span class="promo-copy-label flex shrink-0 items-center gap-1 text-xs font-semibold text-accent-dark dark:text-accent">📋 Sao chép</span>
                </button>
            </div>
        </div>
    @empty
        <div class="empty-box sm:col-span-2 lg:col-span-3">Hiện chưa có khuyến mãi nào đang diễn ra.</div>
    @endforelse
</div>

<script>
function copyPromoCode(btn) {
    const code = btn.dataset.code;
    const label = btn.querySelector('.promo-copy-label');
    const restore = () => { label.textContent = '📋 Sao chép'; };

    const done = () => {
        label.textContent = '✅ Đã chép!';
        setTimeout(restore, 1500);
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(code).then(done).catch(() => fallbackCopy(code, done));
    } else {
        fallbackCopy(code, done);
    }
}

function fallbackCopy(text, done) {
    const el = document.createElement('textarea');
    el.value = text;
    el.style.position = 'fixed';
    el.style.opacity = '0';
    document.body.appendChild(el);
    el.select();
    try { document.execCommand('copy'); done(); } catch (e) { /* im lặng — trình duyệt không hỗ trợ */ }
    document.body.removeChild(el);
}
</script>
@endsection
