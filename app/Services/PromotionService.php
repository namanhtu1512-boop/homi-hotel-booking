<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    public function list(): Collection
    {
        return Promotion::withTrashed()->latest()->get();
    }

    public function activePublic(): Collection
    {
        $today = today()->toDateString();

        return Promotion::active()
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today))
            ->orderBy('ends_at')
            ->get();
    }

    public function find(int $id): Promotion
    {
        return Promotion::withTrashed()->findOrFail($id);
    }

    /**
     * Tìm khuyến mãi hợp lệ theo mã — dùng khi khách nhập mã ở bước đặt phòng.
     *
     * @throws ValidationException
     */
    public function findValidByCode(string $code): Promotion
    {
        $promotion = Promotion::where('code', $code)->first();

        if (! $promotion || ! $promotion->isValidNow()) {
            throw ValidationException::withMessages([
                'promo_code' => ['Mã giảm giá không hợp lệ hoặc đã hết hạn.'],
            ]);
        }

        return $promotion;
    }

    public function create(array $data): Promotion
    {
        return Promotion::create($data);
    }

    public function update(Promotion $promotion, array $data): Promotion
    {
        $promotion->update($data);

        return $promotion->fresh();
    }

    public function delete(Promotion $promotion): void
    {
        $promotion->delete();
    }

    public function restore(Promotion $promotion): void
    {
        $promotion->restore();
    }
}
