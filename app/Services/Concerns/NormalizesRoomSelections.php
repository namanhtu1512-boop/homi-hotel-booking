<?php

namespace App\Services\Concerns;

use App\Models\Booking;
use Illuminate\Validation\ValidationException;

/**
 * Dùng cho LateCheckoutRequestService — chuẩn hóa + validate
 * `room_selections` (map booking_item_id => số lượng phòng của dòng đó
 * khách chọn) khách gửi lên từ form: bỏ entry <= 0, chặn item_id không
 * thuộc đơn, chặn quantity vượt quá số lượng thật của dòng.
 */
trait NormalizesRoomSelections
{
    /**
     * @param  array<int|string, int>|null  $raw
     * @return array<int, int>|null null = khách không chọn gì cụ thể, áp dụng TOÀN BỘ đơn
     */
    private function normalizeRoomSelections(Booking $booking, ?array $raw): ?array
    {
        if (empty($raw)) {
            return null;
        }

        $itemsById = $booking->bookingItems->keyBy('id');
        $selections = [];

        foreach ($raw as $itemId => $quantity) {
            $itemId = (int) $itemId;
            $quantity = (int) $quantity;

            if ($quantity <= 0) {
                continue;
            }

            $item = $itemsById->get($itemId);

            if (! $item) {
                throw ValidationException::withMessages([
                    'room_selections' => ['Danh sách phòng chọn không hợp lệ.'],
                ]);
            }

            if ($quantity > $item->quantity) {
                throw ValidationException::withMessages([
                    'room_selections' => ["Dòng {$item->roomType->name} chỉ có {$item->quantity} phòng, không thể chọn {$quantity}."],
                ]);
            }

            $selections[$itemId] = $quantity;
        }

        if (empty($selections)) {
            throw ValidationException::withMessages([
                'room_selections' => ['Vui lòng chọn ít nhất 1 phòng.'],
            ]);
        }

        return $selections;
    }
}
