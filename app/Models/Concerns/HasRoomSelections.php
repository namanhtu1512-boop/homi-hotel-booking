<?php

namespace App\Models\Concerns;

use App\Models\BookingItem;
use Illuminate\Support\Collection;

/**
 * Dùng chung cho EarlyCheckinRequest/LateCheckoutRequest — cả 2 đều có cột
 * JSON `room_selections` (map booking_item_id => số lượng phòng của dòng đó
 * khách chọn, VD {"92": 1, "93": 1} — chỉ 1/2 phòng Standard của dòng 92)
 * + quan hệ booking() trả về Booking có bookingItems. null/rỗng =
 * áp dụng cho TOÀN BỘ phòng trong đơn (tương thích ngược với các yêu cầu
 * tạo trước khi có tính năng chọn phòng theo số lượng).
 *
 * Cần eager load `booking.bookingItems` (kèm `.roomType` nếu view cần tên)
 * trước khi gọi để tránh N+1.
 */
trait HasRoomSelections
{
    /**
     * @return Collection<int, array{item: BookingItem, quantity: int}>
     */
    public function selectedRoomLines(): Collection
    {
        $bookingItems = $this->booking->bookingItems;

        if (empty($this->room_selections)) {
            return $bookingItems
                ->map(fn (BookingItem $item) => ['item' => $item, 'quantity' => $item->quantity])
                ->values();
        }

        return $bookingItems
            ->map(fn (BookingItem $item) => [
                'item'     => $item,
                'quantity' => min($item->quantity, (int) ($this->room_selections[$item->id] ?? 0)),
            ])
            ->filter(fn (array $line) => $line['quantity'] > 0)
            ->values();
    }
}
