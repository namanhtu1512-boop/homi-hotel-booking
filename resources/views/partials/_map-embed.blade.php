@if ($hotel->address)
    <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
        <iframe
            src="{{ $hotel->mapEmbedUrl() }}"
            width="100%"
            height="260"
            style="border:0"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="Bản đồ vị trí {{ $hotel->name }}"
        ></iframe>
    </div>
    <a href="{{ $hotel->directionsUrl() }}" target="_blank" rel="noopener" class="btn-outline btn-sm mt-2.5 inline-block">📍 Chỉ đường tới {{ $hotel->name }}</a>
@endif
