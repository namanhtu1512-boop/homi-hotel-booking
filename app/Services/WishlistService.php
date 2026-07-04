<?php

namespace App\Services;

use App\Models\RoomType;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class WishlistService
{
    private const MAX_QUANTITY = 10;

    public function list(User $user): Collection
    {
        return $user->wishlistItems()
            ->with('roomType.images')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Thêm loại phòng vào danh sách chờ — nếu đã có sẵn thì cộng dồn quantity
     * (cap tối đa giống rule đặt phòng) thay vì tạo dòng trùng.
     */
    public function add(User $user, int $roomTypeId, int $quantity = 1): WishlistItem
    {
        $roomType = RoomType::where('status', 'active')->findOrFail($roomTypeId);

        $existing = $user->wishlistItems()->where('room_type_id', $roomType->id)->first();

        $newQuantity = min(self::MAX_QUANTITY, ($existing?->quantity ?? 0) + $quantity);

        return WishlistItem::updateOrCreate(
            ['user_id' => $user->id, 'room_type_id' => $roomType->id],
            ['quantity' => $newQuantity],
        );
    }

    public function updateQuantity(User $user, int $itemId, int $quantity): WishlistItem
    {
        $item = $this->findOwned($user, $itemId);

        if ($quantity < 1 || $quantity > self::MAX_QUANTITY) {
            throw ValidationException::withMessages([
                'quantity' => ['Số lượng phải từ 1 đến ' . self::MAX_QUANTITY . '.'],
            ]);
        }

        $item->update(['quantity' => $quantity]);

        return $item->fresh();
    }

    public function updateGuests(User $user, int $itemId, int $adults, int $children): WishlistItem
    {
        $item = $this->findOwned($user, $itemId);

        if ($adults < 1) {
            throw ValidationException::withMessages([
                'adults' => ['Phải có ít nhất 1 người lớn.'],
            ]);
        }

        $item->update(['adults' => $adults, 'children' => max(0, $children)]);

        return $item->fresh();
    }

    public function remove(User $user, int $itemId): void
    {
        $this->findOwned($user, $itemId)->delete();
    }

    private function findOwned(User $user, int $itemId): WishlistItem
    {
        return $user->wishlistItems()->findOrFail($itemId);
    }
}
