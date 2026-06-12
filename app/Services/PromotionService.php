<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    public function list(bool $adminView = false, int $perPage = 15): LengthAwarePaginator
    {
        $query = Promotion::with('hotel')->orderBy('created_at', 'desc');

        if (! $adminView) {
            $query->where('status', 'active')
                  ->where(fn($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()));
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): Promotion
    {
        $this->validateDates($data);

        return Promotion::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'type'        => $data['type'] ?? 'promotion',
            'valid_from'  => $data['valid_from'] ?? null,
            'valid_to'    => $data['valid_to'] ?? null,
            'hotel_id'    => $data['hotel_id'] ?? null,
            'status'      => 'active',
        ]);
    }

    public function update(Promotion $promotion, array $data): Promotion
    {
        $this->validateDates($data);

        $promotion->update(array_filter([
            'name'        => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'valid_from'  => $data['valid_from'] ?? null,
            'valid_to'    => $data['valid_to'] ?? null,
            'hotel_id'    => $data['hotel_id'] ?? null,
        ], fn($v) => ! is_null($v)));

        return $promotion->fresh();
    }

    public function toggleStatus(Promotion $promotion): Promotion
    {
        $promotion->update([
            'status' => $promotion->status === 'active' ? 'inactive' : 'active',
        ]);

        return $promotion->fresh();
    }

    private function validateDates(array $data): void
    {
        if (! empty($data['valid_from']) && ! empty($data['valid_to'])) {
            if ($data['valid_from'] > $data['valid_to']) {
                throw ValidationException::withMessages([
                    'valid_from' => ['Ngày bắt đầu không được sau ngày kết thúc.'],
                ]);
            }
        }
    }
}
