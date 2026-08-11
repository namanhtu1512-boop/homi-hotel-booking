<?php

namespace App\Services;

use App\Enums\SurchargeCategory;
use App\Models\SurchargeItem;
use Illuminate\Database\Eloquent\Collection;

class SurchargeItemService
{
    public function list(): Collection
    {
        return SurchargeItem::withTrashed()->latest()->get();
    }

    /**
     * Danh mục active dùng cho combobox tìm kiếm gợi ý khi ghi phụ phí phát
     * sinh tại trang chi tiết đơn (admin/staff). $category lọc theo 1 trong 3
     * form phụ phí (hỏng/mất đồ, vi phạm, vệ sinh đặc biệt) — bỏ trống để lấy
     * tất cả.
     */
    public function activePublic(?SurchargeCategory $category = null): Collection
    {
        return SurchargeItem::active()
            ->when($category, fn ($query) => $query->category($category))
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): SurchargeItem
    {
        return SurchargeItem::withTrashed()->findOrFail($id);
    }

    public function create(array $data): SurchargeItem
    {
        return SurchargeItem::create($data);
    }

    public function update(SurchargeItem $item, array $data): SurchargeItem
    {
        $item->update($data);

        return $item->fresh();
    }

    public function delete(SurchargeItem $item): void
    {
        $item->delete();
    }

    public function restore(SurchargeItem $item): void
    {
        $item->restore();
    }
}
