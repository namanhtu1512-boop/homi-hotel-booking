<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function create(User $customer, int $hotelId, array $data): Review
    {
        $hotel = Hotel::where('is_active', true)->findOrFail($hotelId);

        $hasEligibleBooking = $customer->bookings()
            ->whereHas(
                'bookingItems.roomType',
                fn($q) => $q->where('hotel_id', $hotel->id)
            )
            ->whereIn('status', ['confirmed', 'checked_out'])
            ->exists();

        if (! $hasEligibleBooking) {
            throw ValidationException::withMessages([
                'hotel_id' => ['Bạn chưa có lượt lưu trú tại khách sạn này.'],
            ]);
        }

        $alreadyReviewed = Review::where('user_id', $customer->id)
            ->where('hotel_id', $hotel->id)
            ->exists();

        if ($alreadyReviewed) {
            throw ValidationException::withMessages([
                'hotel_id' => ['Bạn đã đánh giá khách sạn này rồi.'],
            ]);
        }

        return Review::create([
            'user_id'  => $customer->id,
            'hotel_id' => $hotel->id,
            'rating'   => $data['rating'],
            'comment'  => $data['comment'] ?? null,
        ]);
    }

    public function listByHotel(int $hotelId, int $perPage = 10): LengthAwarePaginator
    {
        Hotel::where('is_active', true)->findOrFail($hotelId);

        return Review::where('hotel_id', $hotelId)
            ->where('is_visible', true)
            ->with(['user' => fn($q) => $q->select('id', 'name')])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function averageRating(int $hotelId): ?float
    {
        $avg = Review::where('hotel_id', $hotelId)->where('is_visible', true)->avg('rating');

        return $avg ? round((float) $avg, 1) : null;
    }
}
