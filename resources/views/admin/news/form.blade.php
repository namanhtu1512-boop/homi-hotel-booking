@extends('layouts.admin')

@php
    $isEdit = $article !== null;
@endphp

@section('title', ($isEdit ? 'Sửa tin tức' : 'Viết bài mới') . ' · Homi Admin')
@section('page_title', $isEdit ? 'Sửa tin tức' : 'Viết bài mới')
@section('page_subtitle', 'Các trường có dấu * là bắt buộc.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.news.index') }}" class="btn btn-outline">Quay lại danh sách</a>
    </div>

    <form method="POST"
        action="{{ $isEdit ? route('admin.news.update', $article->id) : route('admin.news.store') }}"
        class="form-grid">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="title">Tiêu đề *</label>
            <input id="title" type="text" name="title" value="{{ old('title', $article->title ?? '') }}" required>
        </div>

        <div class="form-group">
            <label for="excerpt">Mô tả ngắn</label>
            <input id="excerpt" type="text" name="excerpt" value="{{ old('excerpt', $article->excerpt ?? '') }}">
        </div>

        <div class="form-group">
            <label for="cover_image">Ảnh bìa (URL)</label>
            <input id="cover_image" type="text" name="cover_image" value="{{ old('cover_image', $article->cover_image ?? '') }}" placeholder="https://...">
        </div>

        <div class="form-group">
            <label for="content">Nội dung</label>
            <textarea id="content" name="content" rows="10">{{ old('content', $article->content ?? '') }}</textarea>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="published_at">Ngày đăng</label>
                <input id="published_at" type="date" name="published_at" value="{{ old('published_at', optional($article->published_at ?? null)->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label for="status">Trạng thái *</label>
                <select id="status" name="status" required>
                    <option value="published" @selected(old('status', $article->status ?? 'published') === 'published')>Đăng ngay</option>
                    <option value="draft" @selected(old('status', $article->status ?? '') === 'draft')>Nháp</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">{{ $isEdit ? 'Lưu thay đổi' : 'Đăng bài' }}</button>
    </form>
</div>
@endsection
