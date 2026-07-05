<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\NewsService;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function __construct(private readonly NewsService $newsService) {}

    public function index(): View
    {
        return view('client.news.index', ['articles' => $this->newsService->publicList()]);
    }

    public function show(string $slug): View
    {
        return view('client.news.show', ['article' => $this->newsService->findPublishedBySlug($slug)]);
    }
}
