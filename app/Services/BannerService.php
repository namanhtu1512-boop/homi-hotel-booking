<?php

namespace App\Services;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Collection;

class BannerService
{
    public function list(): Collection
    {
        return Banner::orderBy('sort_order')->get();
    }

    public function activeOrdered(): Collection
    {
        return Banner::active()->orderBy('sort_order')->get();
    }

    public function find(int $id): Banner
    {
        return Banner::findOrFail($id);
    }

    public function create(array $data): Banner
    {
        return Banner::create($data);
    }

    public function update(Banner $banner, array $data): Banner
    {
        $banner->update($data);

        return $banner->fresh();
    }

    public function delete(Banner $banner): void
    {
        $banner->delete();
    }
}
