<?php

namespace App\Services;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Collection;

class AmenityService
{
    public function list(): Collection
    {
        return Amenity::withCount('hotels')->orderBy('name')->get();
    }

    public function create(array $data): Amenity
    {
        return Amenity::create([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
        ]);
    }

    public function update(Amenity $amenity, array $data): Amenity
    {
        $amenity->update([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
        ]);

        return $amenity->fresh();
    }

    public function delete(Amenity $amenity): void
    {
        $amenity->delete();
    }
}
