<?php

namespace App\Services;

use App\Models\HotelInfo;
use App\Models\RoomType;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * Lưu danh sách đường dẫn ảnh cho khách sạn.
     *
     * @param  string[]  $paths
     */
    public function syncHotelInfoImages(HotelInfo $hotel, array $paths, bool $replace = false): void
    {
        if ($replace) {
            $this->deleteFiles($hotel->images()->pluck('path'));
            $hotel->images()->delete();
        }

        $offset = $replace ? 0 : $hotel->images()->count();

        foreach ($paths as $index => $path) {
            $hotel->images()->create([
                'path'       => $path,
                'sort_order' => $offset + $index,
            ]);
        }
    }

    /**
     * Lưu danh sách đường dẫn ảnh cho loại phòng.
     *
     * @param  string[]  $paths
     */
    public function syncRoomTypeImages(RoomType $roomType, array $paths, bool $replace = false): void
    {
        if ($replace) {
            $this->deleteFiles($roomType->images()->pluck('path'));
            $roomType->images()->delete();
        }

        $offset = $replace ? 0 : $roomType->images()->count();

        foreach ($paths as $index => $path) {
            $roomType->images()->create([
                'path'       => $path,
                'sort_order' => $offset + $index,
            ]);
        }
    }

    /**
     * Xóa một ảnh khách sạn theo ID, sắp xếp lại sort_order sau khi xóa.
     */
    public function deleteHotelInfoImage(HotelInfo $hotel, int $imageId): bool
    {
        $image = $hotel->images()->find($imageId);

        if (! $image) {
            return false;
        }

        $this->deleteFiles([$image->path]);
        $image->delete();
        $this->reorderImages($hotel->images()->orderBy('sort_order')->get());

        return true;
    }

    /**
     * Xóa một ảnh loại phòng theo ID, sắp xếp lại sort_order sau khi xóa.
     */
    public function deleteRoomTypeImage(RoomType $roomType, int $imageId): bool
    {
        $image = $roomType->images()->find($imageId);

        if (! $image) {
            return false;
        }

        $this->deleteFiles([$image->path]);
        $image->delete();
        $this->reorderImages($roomType->images()->orderBy('sort_order')->get());

        return true;
    }

    /**
     * Đặt lại sort_order liên tiếp bắt đầu từ 0 cho danh sách ảnh.
     */
    private function reorderImages(\Illuminate\Database\Eloquent\Collection $images): void
    {
        foreach ($images as $index => $image) {
            $image->update(['sort_order' => $index]);
        }
    }

    /**
     * Xóa file vật lý trên disk 'public' cho các path do chính hệ thống lưu
     * (upload qua Storage::store, dạng "hotel/xxx.jpg"). Path dạng URL đầy đủ
     * (admin dán link ảnh ngoài qua ô images_text) không khớp file cục bộ nào
     * nên Storage::delete() chỉ trả về false, không ném lỗi — an toàn để gọi
     * chung cho cả hai loại path.
     *
     * @param  iterable<string>  $paths
     */
    private function deleteFiles(iterable $paths): void
    {
        foreach ($paths as $path) {
            if ($path && ! str_starts_with($path, 'http://') && ! str_starts_with($path, 'https://')) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
