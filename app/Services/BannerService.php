<?php

namespace App\Services;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

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
        $oldPath = $banner->image_path;

        $banner->update($data);

        // Ảnh cũ bị thay bằng ảnh mới (upload file hoặc đổi sang image_url)
        // — xóa file vật lý cũ trên disk 'public' để tránh rác tồn đọng.
        if (isset($data['image_path']) && $data['image_path'] !== $oldPath) {
            $this->deleteFile($oldPath);
        }

        return $banner->fresh();
    }

    public function delete(Banner $banner): void
    {
        $this->deleteFile($banner->image_path);
        $banner->delete();
    }

    private function deleteFile(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http://') && ! str_starts_with($path, 'https://')) {
            Storage::disk('public')->delete($path);
        }
    }
}
