<?php

namespace App\Services;

use App\Models\Hotel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class HotelService
{
    public function __construct(private readonly ImageService $imageService) {}

    // --- Admin API ---

    public function adminList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Hotel::withTrashed()->withCount('roomTypes');

        if (! empty($filters['search'])) {
            $keyword = $filters['search'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('city', 'like', "%{$keyword}%");
            });
        }

        if (! empty($filters['status'])) {
            if ($filters['status'] === 'deleted') {
                $query->onlyTrashed();
            } else {
                $query->where('status', $filters['status']);
            }
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function adminFind(int $id): Hotel
    {
        return Hotel::withTrashed()
            ->with(['images', 'amenities'])
            ->withCount('roomTypes')
            ->findOrFail($id);
    }

    public function create(array $data): Hotel
    {
        $hotel = Hotel::create([
            'name'        => $data['name'],
            'slug'        => Str::slug($data['name']),
            'city'        => $data['city'],
            'district'    => $data['district'] ?? null,
            'address'     => $data['address'],
            'description' => $data['description'] ?? null,
            'star_rating' => $data['star_rating'] ?? null,
            'status'      => 'active',
        ]);

        if (! empty($data['amenity_ids'])) {
            $hotel->amenities()->sync($data['amenity_ids']);
        }

        if (! empty($data['images'])) {
            $this->imageService->syncHotelImages($hotel, $data['images']);
        }

        return $hotel->load(['amenities', 'images']);
    }

    public function update(Hotel $hotel, array $data): Hotel
    {
        $fields = array_filter([
            'name'        => $data['name'] ?? null,
            'city'        => $data['city'] ?? null,
            'district'    => $data['district'] ?? null,
            'address'     => $data['address'] ?? null,
            'description' => $data['description'] ?? null,
            'star_rating' => $data['star_rating'] ?? null,
        ], fn ($v) => $v !== null);

        if (isset($data['name'])) {
            $fields['slug'] = Str::slug($data['name']);
        }

        $hotel->update($fields);

        if (isset($data['amenity_ids'])) {
            $hotel->amenities()->sync($data['amenity_ids']);
        }

        if (! empty($data['images'])) {
            // replace = true: thay toàn bộ ảnh khi update
            $this->imageService->syncHotelImages($hotel, $data['images'], replace: true);
        }

        return $hotel->fresh(['amenities', 'images']);
    }

    public function softDelete(Hotel $hotel): void
    {
        $hotel->delete();
    }

    public function restore(Hotel $hotel): void
    {
        $hotel->restore();
    }

    public function toggleStatus(Hotel $hotel): Hotel
    {
        $hotel->update([
            'status' => $hotel->status === 'active' ? 'hidden' : 'active',
        ]);

        return $hotel->fresh();
    }

    // --- Public API ---

    public function publicList(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Hotel::where('status', 'active')
            ->with([
                'images' => fn ($q) => $q->orderBy('sort_order')->limit(1),
                'amenities',
            ])
            ->withMin('roomTypes', 'price_per_night');

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
                $query->whereHas('amenities', fn ($q) => $q->where('amenities.id', $amenityId));
            }
        }

        if (! empty($filters['min_price'])) {
            $query->whereHas('roomTypes', fn ($q) => $q->where('price_per_night', '>=', $filters['min_price']));
        }

        if (! empty($filters['max_price'])) {
            $query->whereHas('roomTypes', fn ($q) => $q->where('price_per_night', '<=', $filters['max_price']));
        }

        return $query->orderBy('star_rating', 'desc')->paginate($perPage);
    }

    public function publicFind(int $id): Hotel
    {
        return Hotel::where('status', 'active')
            ->with([
                'images',
                'amenities',
                'activeRoomTypes.images',
            ])
            ->findOrFail($id);
    }
}
