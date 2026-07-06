<?php

namespace App\Services;

use App\Models\SeasonalRate;
use Illuminate\Database\Eloquent\Collection;

class SeasonalRateService
{
    public function list(): Collection
    {
        return SeasonalRate::with('roomType')->latest()->get();
    }

    public function find(int $id): SeasonalRate
    {
        return SeasonalRate::findOrFail($id);
    }

    public function create(array $data): SeasonalRate
    {
        return SeasonalRate::create($data);
    }

    public function update(SeasonalRate $seasonalRate, array $data): SeasonalRate
    {
        $seasonalRate->update($data);

        return $seasonalRate->fresh();
    }

    public function delete(SeasonalRate $seasonalRate): void
    {
        $seasonalRate->delete();
    }

    /**
     * Toàn bộ rate active có thể áp dụng cho 1 room type trong khoảng ngày
     * (nạp 1 lần cho cả booking line, tránh query lại theo từng đêm).
     */
    public function ratesForRoomType(int $roomTypeId, string $checkIn, string $checkOut): Collection
    {
        return SeasonalRate::active()
            ->where(fn ($q) => $q->whereNull('room_type_id')->orWhere('room_type_id', $roomTypeId))
            ->where('start_date', '<', $checkOut)
            ->where('end_date', '>=', $checkIn)
            ->get();
    }
}
