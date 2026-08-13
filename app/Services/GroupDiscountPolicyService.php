<?php

namespace App\Services;

use App\Models\GroupDiscountPolicy;
use Illuminate\Database\Eloquent\Collection;

class GroupDiscountPolicyService
{
    public function list(): Collection
    {
        return GroupDiscountPolicy::withTrashed()->orderBy('min_rooms')->get();
    }

    public function find(int $id): GroupDiscountPolicy
    {
        return GroupDiscountPolicy::withTrashed()->findOrFail($id);
    }

    public function create(array $data): GroupDiscountPolicy
    {
        return GroupDiscountPolicy::create($data);
    }

    public function update(GroupDiscountPolicy $policy, array $data): GroupDiscountPolicy
    {
        $policy->update($data);

        return $policy->fresh();
    }

    public function delete(GroupDiscountPolicy $policy): void
    {
        $policy->delete();
    }

    public function restore(GroupDiscountPolicy $policy): void
    {
        $policy->restore();
    }

    /**
     * Bậc ưu đãi cao nhất mà $roomCount đạt được trong số các chính sách
     * đang active — bậc càng cao (min_rooms lớn) càng ưu tiên, không cộng
     * dồn nhiều bậc cùng lúc.
     */
    public function matchTierFor(int $roomCount): ?GroupDiscountPolicy
    {
        return GroupDiscountPolicy::active()
            ->where('min_rooms', '<=', $roomCount)
            ->orderByDesc('min_rooms')
            ->first();
    }
}
