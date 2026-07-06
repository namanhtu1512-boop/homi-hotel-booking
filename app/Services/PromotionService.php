<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
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
                'promo_codes' => ["Mã giảm giá \"{$code}\" không hợp lệ hoặc đã hết hạn."],
            ]);
        }

        return $promotion;
    }

    /**
     * Tìm nhiều mã khuyến mãi hợp lệ cùng lúc (stack) — mỗi mã phải hợp lệ
     * riêng lẻ (tái dùng findValidByCode), và nếu có từ 2 mã trở lên thì
     * TẤT CẢ phải được đánh dấu stackable=true, tránh khách cộng dồn 2 mã
     * giảm giá không dự tính stack chung (VD 2 mã 50% làm phòng miễn phí).
     *
     * @param  array<int, string>  $codes
     * @return SupportCollection<int, Promotion>
     *
     * @throws ValidationException
     */
    public function findValidManyByCodes(array $codes): SupportCollection
    {
        $promotions = collect($codes)->map(fn (string $code) => $this->findValidByCode($code));

        if ($promotions->count() > 1 && $promotions->contains(fn (Promotion $p) => ! $p->stackable)) {
            throw ValidationException::withMessages([
                'promo_codes' => ['Một hoặc nhiều mã không hỗ trợ dùng chung với mã khác — mỗi đơn chỉ được dùng 1 mã không stack.'],
            ]);
        }

        return $promotions;
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
