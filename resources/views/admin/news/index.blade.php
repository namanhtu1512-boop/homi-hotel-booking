@extends('layouts.admin')

@section('title', 'Tin tức · Homi Admin')
@section('page_title', 'Tin tức')
@section('page_subtitle', 'Quản lý bài viết hiển thị ở trang công khai /news.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.news.create') }}" class="btn btn-primary">+ Viết bài mới</a>
    </div>

    @if ($articles->isEmpty())
        <div class="empty-box">Chưa có bài viết nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Ngày đăng</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($articles as $article)
                        <tr>
                            <td>{{ $article->title }}</td>
                            <td>{{ $article->published_at?->format('d/m/Y') ?? '—' }}</td>
                            <td><span class="badge {{ $article->status === 'published' ? 'badge-green' : 'badge-orange' }}">{{ $article->status === 'published' ? 'Đã đăng' : 'Nháp' }}</span></td>
                            <td>
                                <div class="action-row">
                                    <a href="{{ route('admin.news.edit', $article->id) }}" class="btn btn-outline btn-sm">Sửa</a>
                                    <form method="POST" action="{{ route('admin.news.destroy', $article->id) }}" onsubmit="return confirm('Xóa bài viết này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
