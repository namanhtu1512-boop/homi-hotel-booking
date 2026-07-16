<?php

namespace App\Services;

use App\Models\News;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class NewsService
{
    public function list(): Collection
    {
        return News::latest('published_at')->get();
    }

    public function publicList(int $perPage = 9): LengthAwarePaginator
    {
        return News::published()->orderByDesc('published_at')->paginate($perPage);
    }

    public function latestPublished(int $limit = 3): Collection
    {
        return News::published()->orderByDesc('published_at')->limit($limit)->get();
    }

    public function find(int $id): News
    {
        return News::findOrFail($id);
    }

    public function findPublishedBySlug(string $slug): News
    {
        return News::published()->where('slug', $slug)->firstOrFail();
    }

    public function create(array $data): News
    {
        $data['slug'] = $this->uniqueSlug($data['title']);

        return News::create($data);
    }

    public function update(News $news, array $data): News
    {
        if (isset($data['title'])) {
            $data['slug'] = $this->uniqueSlug($data['title'], $news->id);
        }

        $news->update($data);

        return $news->fresh();
    }

    public function delete(News $news): void
    {
        $news->delete();
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 2;

        while (
            News::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
