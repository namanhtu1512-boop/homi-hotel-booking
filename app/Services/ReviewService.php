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
     * Đơn đã xác nhận (confirmed) hoặc hoàn tất (completed) của khách nhưng
     * từng loại phòng trong đơn chưa được đánh giá — dùng để hiển thị nút
     * "Viết đánh giá". Theo acceptance criteria US11: "confirmed/completed
     * mới được đánh giá".
     */
    public function reviewableItems(User $user): \Illuminate\Support\Collection
    {
        $bookings = Booking::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::COMPLETED])
            ->with('bookingItems.roomType')
            ->get();

        $reviewed = Review::where('user_id', $user->id)
            ->get(['booking_id', 'room_type_id'])
            ->map(fn ($r) => $r->booking_id . '-' . $r->room_type_id)
            ->all();

        $items = collect();

        foreach ($bookings as $booking) {
            foreach ($booking->bookingItems as $item) {
                if (! $item->roomType) {
                    continue;
                }

                $key = $booking->id . '-' . $item->room_type_id;

                if (in_array($key, $reviewed, true)) {
                    continue;
                }

                $items->push([
                    'booking'   => $booking,
                    'room_type' => $item->roomType,
                ]);
            }
        }

        return $items->values();
    }

    public function create(User $user, array $data): Review
    {
        $booking = Booking::where('user_id', $user->id)->findOrFail($data['booking_id']);

        if (! in_array($booking->status, [BookingStatus::CONFIRMED, BookingStatus::COMPLETED], true)) {
            throw ValidationException::withMessages([
                'booking_id' => ['Chỉ có thể đánh giá đơn đã được xác nhận hoặc đã hoàn tất.'],
            ]);
        }

        $ownsRoomType = $booking->bookingItems()->where('room_type_id', $data['room_type_id'])->exists();

        if (! $ownsRoomType) {
            throw ValidationException::withMessages([
                'room_type_id' => ['Loại phòng này không thuộc đơn đã chọn.'],
            ]);
        }

        $alreadyReviewed = Review::where('booking_id', $booking->id)
            ->where('room_type_id', $data['room_type_id'])
            ->exists();

        if ($alreadyReviewed) {
            throw ValidationException::withMessages([
                'room_type_id' => ['Bạn đã đánh giá loại phòng này cho đơn này rồi.'],
            ]);
        }

        // check-then-act: exists() ở trên không chống được 2 request submit
        // cùng lúc cho cùng booking+room_type — unique constraint ở DB mới là
        // chốt chặn thật, bắt QueryException để trả lỗi validation thân thiện
        // thay vì để nó vỡ thành 500.
        try {
            return Review::create([
                'booking_id'   => $booking->id,
                'room_type_id' => $data['room_type_id'],
                'user_id'      => $user->id,
                'rating'       => $data['rating'],
                'comment'      => $data['comment'] ?? null,
                'images'       => $data['images'] ?? null,
                'status'       => 'visible',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                throw ValidationException::withMessages([
                    'room_type_id' => ['Bạn đã đánh giá loại phòng này cho đơn này rồi.'],
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

        $review->delete();
    }
}
