<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    /**
     * Đơn có thể đánh giá: thuộc về khách, đã hoàn tất, và chưa từng được
     * đánh giá — mỗi đơn chỉ được đánh giá đúng 1 lần.
     */
    public function canReview(Booking $booking, User $user): bool
    {
        return $booking->user_id === $user->id
            && $booking->status === BookingStatus::COMPLETED
            && ! Review::where('booking_id', $booking->id)->exists();
    }

    public function create(User $user, array $data): Review
    {
        $booking = Booking::where('user_id', $user->id)
            ->with('bookingItems')
            ->findOrFail($data['booking_id']);

        if ($booking->status !== BookingStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'booking_id' => ['Chỉ có thể đánh giá sau khi đơn đã hoàn tất.'],
            ]);
        }

        $alreadyReviewed = Review::where('booking_id', $booking->id)->exists();

        if ($alreadyReviewed) {
            throw ValidationException::withMessages([
                'booking_id' => ['Bạn đã đánh giá đơn này rồi.'],
            ]);
        }

        $roomTypeId = $booking->bookingItems->first()?->room_type_id;

        if (! $roomTypeId) {
            throw ValidationException::withMessages([
                'booking_id' => ['Đơn này không có loại phòng hợp lệ để đánh giá.'],
            ]);
        }

        // check-then-act: exists() ở trên không chống được 2 request submit
        // cùng lúc cho cùng đơn — unique constraint ở DB mới là chốt chặn
        // thật, bắt QueryException để trả lỗi validation thân thiện thay vì
        // để nó vỡ thành 500.
        try {
            return Review::create([
                'booking_id'   => $booking->id,
                'room_type_id' => $roomTypeId,
                'user_id'      => $user->id,
                'rating'       => $data['rating'],
                'comment'      => $data['comment'] ?? null,
                'images'       => $data['images'] ?? null,
                'videos'       => $data['videos'] ?? null,
                'status'       => 'visible',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                throw ValidationException::withMessages([
                    'booking_id' => ['Bạn đã đánh giá đơn này rồi.'],
                ]);
            }

            throw $e;
        }
    }

    public function forRoomType(int $roomTypeId, int $limit = 20): Collection
    {
        return Review::visible()
            ->where('room_type_id', $roomTypeId)
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{avg: float, count: int}
     */
    public function summaryFor(int $roomTypeId): array
    {
        $row = Review::visible()->where('room_type_id', $roomTypeId)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        return [
            'avg'   => round((float) ($row->avg_rating ?? 0), 1),
            'count' => (int) ($row->total ?? 0),
        ];
    }

    /**
     * Điểm trung bình cho nhiều loại phòng cùng lúc — tránh N+1 khi hiển thị danh sách.
     *
     * @return array<int, array{avg: float, count: int}>
     */
    public function summaryForMany(array $roomTypeIds): array
    {
        if (empty($roomTypeIds)) {
            return [];
        }

        $rows = Review::visible()
            ->whereIn('room_type_id', $roomTypeIds)
            ->groupBy('room_type_id')
            ->selectRaw('room_type_id, AVG(rating) as avg_rating, COUNT(*) as total')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->room_type_id] = [
                'avg'   => round((float) $row->avg_rating, 1),
                'count' => (int) $row->total,
            ];
        }

        return $result;
    }

    public function latestVisible(int $limit = 6): Collection
    {
        return Review::visible()
            ->with(['user', 'roomType'])
            ->orderByDesc('rating')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function adminList(array $filters = [])
    {
        $query = Review::with(['user', 'roomType']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(15)->withQueryString();
    }

    public function toggleStatus(Review $review): Review
    {
        $review->update([
            'status' => $review->status === 'visible' ? 'hidden' : 'visible',
        ]);

        return $review->fresh();
    }

    public function delete(Review $review): void
    {
        if ($review->images) {
            Storage::disk('public')->delete($review->images);
        }

        if ($review->videos) {
            Storage::disk('public')->delete($review->videos);
        }

        $review->delete();
    }
}
