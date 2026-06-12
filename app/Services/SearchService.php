<?php

namespace App\Services;

use App\Models\Hotel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class SearchService
{
    public function search(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $this->validateFilters($filters);

        $query = Hotel::where('is_active', true)
            ->with([
                'images'    => fn($q) => $q->orderBy('sort_order')->limit(1),
                'amenities' => fn($q) => $q->limit(5),
            ])
            ->withMin('roomTypes', 'base_price');

        if (! empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('city', 'like', "%{$keyword}%")
                  ->orWhere('address', 'like', "%{$keyword}%");
            });
        }

        if (! empty($filters['city'])) {
            $query->whereRaw('LOWER(city) = ?', [strtolower($filters['city'])]);
        }

        if (! empty($filters['min_price'])) {
            $query->whereHas(
                'roomTypes',
                fn($q) => $q->where('base_price', '>=', $filters['min_price'])->where('is_active', true)
            );
        }

        if (! empty($filters['max_price'])) {
            $query->whereHas(
                'roomTypes',
                fn($q) => $q->where('base_price', '<=', $filters['max_price'])->where('is_active', true)
            );
        }

        if (! empty($filters['amenities'])) {
            foreach ((array) $filters['amenities'] as $amenityId) {
                $query->whereHas('amenities', fn($q) => $q->where('amenities.id', $amenityId));
            }
        }

        $sort = $filters['sort'] ?? 'rating';
        match ($sort) {
            'price_asc'  => $query->orderBy('room_types_min_base_price', 'asc'),
            'price_desc' => $query->orderBy('room_types_min_base_price', 'desc'),
            default      => $query->orderBy('star_rating', 'desc'),
        };

        return $query->paginate($perPage);
    }

    private function validateFilters(array $filters): void
    {
        if (! empty($filters['check_in']) && ! empty($filters['check_out'])) {
            if ($filters['check_in'] >= $filters['check_out']) {
                throw ValidationException::withMessages([
                    'check_in' => ['Ngày trả phòng phải sau ngày nhận phòng.'],
                ]);
            }
        }

        if (! empty($filters['min_price']) && ! empty($filters['max_price'])) {
            if ($filters['min_price'] > $filters['max_price']) {
                throw ValidationException::withMessages([
                    'min_price' => ['Giá tối thiểu không được lớn hơn giá tối đa.'],
                ]);
            }
        }
    }
}
