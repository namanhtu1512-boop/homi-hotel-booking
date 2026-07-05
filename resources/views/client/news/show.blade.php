@extends('layouts.app')

@section('title', $article->title . ' · Homi')
@section('banner_tag', 'Tin tức')
@section('banner_title', $article->title)
@section('banner_subtitle', $article->published_at?->format('d/m/Y') ?? '')

@section('content')
<div class="card">
    @if ($article->cover_image_url)
        <img src="{{ $article->cover_image_url }}" class="mb-5 aspect-video w-full rounded-2xl object-cover" alt="">
    @endif
    <div class="prose max-w-none text-sm leading-relaxed whitespace-pre-line text-slate-600 dark:text-slate-300">{{ $article->content }}</div>

    <a href="{{ route('news.index') }}" class="btn-outline mt-6 inline-block">← Xem tất cả tin tức</a>
</div>
@endsection
