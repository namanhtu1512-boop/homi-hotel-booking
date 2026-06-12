<?php

namespace App\Services;

use App\Models\Hotel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class HotelService
{
    public function adminList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Hotel::withCount('roomTypes');

        if (! empty($filters['search'])) {
            $keyword = $filters['search'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('city', 'like', "%{$keyword}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function adminFind(int $id): Hotel
    {
        return Hotel::withCount('roomTypes')->findOrFail($id);
    }

    public function create(array $data): Hotel
    {
        $hotel = Hotel::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'address'     => $data['address'] ?? null,
            'city'        => $data['city'] ?? null,
            'country'     => $data['country'] ?? null,
            'star_rating' => $data['star_rating'] ?? null,
            'phone'       => $data['phone'] ?? null,
            'email'       => $data['email'] ?? null,
            'is_active'   => true,
        ]);

        if (! empty($data['amenity_ids'])) {
            $hotel->amenities()->sync($data['amenity_ids']);
        }

        if (! empty($data['images'])) {
            foreach ($data['images'] as $index => $path) {
                $hotel->images()->create(['image_path' => $path, 'sort_order' => $index]);
            }
        }

        return $hotel->load(['amenities', 'images']);
    }

    public function update(Hotel $hotel, array $data): Hotel
    {
        $hotel->update(array_filter([
            'name'        => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'address'     => $data['address'] ?? null,
            'city'        => $data['city'] ?? null,
            'country'     => $data['country'] ?? null,
            'star_rating' => $data['star_rating'] ?? null,
            'phone'       => $data['phone'] ?? null,
            'email'       => $data['email'] ?? null,
        ], fn($v) => ! is_null($v)));

        if (isset($data['amenity_ids'])) {
            $hotel->amenities()->sync($data['amenity_ids']);
        }

        return $hotel->fresh(['amenities', 'images']);
    }

    public function softDelete(Hotel $hotel): void
    {
        $hotel->delete();
    }

    public function toggleActive(Hotel $hotel): Hotel
    {
        $hotel->update(['is_active' => ! $hotel->is_active]);

        return $hotel->fresh();
    }

    // --- Public API ---

    public function publicList(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Hotel::where('is_active', true)
            ->with(['images' => fn($q) => $q->orderBy('sort_order')->limit(1), 'amenities'])
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

        if (! empty($filters['amenities'])) {
            foreach ($filters['amenities'] as $amenityId) {
                $query->whereHas('amenities', fn($q) => $q->where('amenities.id', $amenityId));
            }
        }

        return $query->orderBy('star_rating', 'desc')->paginate($perPage);
    }

    public function publicFind(int $id): Hotel
    {
        return Hotel::where('is_active', true)
            ->with([
                'images',
                'amenities',
                'roomTypes' => fn($q) => $q->where('is_active', true)->with('images'),
            ])
            ->findOrFail($id);
    }
}
