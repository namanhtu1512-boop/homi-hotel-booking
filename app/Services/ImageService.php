<?php

namespace App\Services;

use App\Models\HotelInfo;
use App\Models\RoomType;

class ImageService
{
    /**
     * Lưu danh sách đường dẫn ảnh cho khách sạn.
     *
     * @param  string[]  $paths
     */
    public function syncHotelImages(HotelInfo $hotel, array $paths, bool $replace = false): void
    {
        if ($replace) {
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
    public function deleteHotelImage(HotelInfo $hotel, int $imageId): bool
    {
        $image = $hotel->images()->find($imageId);

        if (! $image) {
            return false;
        }

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
}
