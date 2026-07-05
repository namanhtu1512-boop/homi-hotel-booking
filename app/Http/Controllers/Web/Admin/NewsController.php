<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\NewsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function __construct(
        private readonly NewsService $newsService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): View
    {
        return view('admin.news.index', ['articles' => $this->newsService->list()]);
    }

    public function create(): View
    {
        return view('admin.news.form', ['article' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateNews($request);

        $article = $this->newsService->create($data);

        $this->auditLog->log('news.created', $article, "Tạo tin tức \"{$article->title}\".");

        return redirect()->route('admin.news.index')->with('success', "Đã tạo tin tức \"{$article->title}\".");
    }

    public function edit(int $id): View
    {
        return view('admin.news.form', ['article' => $this->newsService->find($id)]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $article = $this->newsService->find($id);
        $data = $this->validateNews($request);

        $this->newsService->update($article, $data);

        $this->auditLog->log('news.updated', $article->fresh(), "Cập nhật tin tức \"{$article->title}\".");

        return redirect()->route('admin.news.index')->with('success', "Đã cập nhật tin tức \"{$article->title}\".");
    }

    public function destroy(int $id): RedirectResponse
    {
        $article = $this->newsService->find($id);
        $title = $article->title;

        $this->newsService->delete($article);

        $this->auditLog->log('news.deleted', null, "Xóa tin tức \"{$title}\".");

        return redirect()->route('admin.news.index')->with('success', "Đã xóa tin tức \"{$title}\".");
    }

    private function validateNews(Request $request): array
    {
        return $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'excerpt'      => ['nullable', 'string', 'max:500'],
            'content'      => ['nullable', 'string'],
            'cover_image'  => ['nullable', 'string', 'max:2000'],
            'status'       => ['required', 'in:published,draft'],
            'published_at' => ['nullable', 'date'],
        ], [], [
            'title'        => 'tiêu đề',
            'excerpt'      => 'mô tả ngắn',
            'content'      => 'nội dung',
            'cover_image'  => 'ảnh bìa',
            'status'       => 'trạng thái',
            'published_at' => 'ngày đăng',
        ]);
    }
}
