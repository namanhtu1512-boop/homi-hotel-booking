<?php

namespace App\Services;

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
     * sinh tại trang chi tiết đơn (admin/staff).
     */
    public function activePublic(): Collection
    {
        return SurchargeItem::active()->orderBy('name')->get();
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
