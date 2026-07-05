@extends('layouts.app')

@section('title', 'Tin tức · Homi')
@section('banner_tag', 'Tin tức')
@section('banner_title', 'Tin tức & Cập nhật')
@section('banner_subtitle', 'Thông tin mới nhất từ Homi Hotel.')

@section('content')
@if ($articles->isEmpty())
    <div class="empty-box">Chưa có bài viết nào.</div>
@else
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($articles as $article)
            <a href="{{ route('news.show', $article->slug) }}" class="card flex flex-col overflow-hidden !p-0 transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="aspect-video bg-primary-light/50 dark:bg-primary/10">
                    @if ($article->cover_image_url)
                        <img src="{{ $article->cover_image_url }}" class="h-full w-full object-cover" alt="">
                    @endif
                </div>
                <div class="flex flex-1 flex-col gap-2 p-4">
                    <span class="text-xs font-semibold text-slate-400">{{ $article->published_at?->format('d/m/Y') }}</span>
                    <h3 class="font-heading text-lg font-bold text-slate-900 dark:text-white">{{ $article->title }}</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $article->excerpt }}</p>
                </div>
            </a>
        @endforeach
    </div>

    <div class="mt-8 flex justify-center">{{ $articles->links() }}</div>
@endif
@endsection
