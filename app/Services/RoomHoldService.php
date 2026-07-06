<?php

namespace App\Services;

use App\Models\RoomHold;

/**
 * Giữ chỗ tạm thời (10-15 phút) khi khách đang điền form đặt phòng, để tránh
 * người khác đặt hết phòng trong lúc khách đầu tiên chưa bấm "Đặt phòng".
 * Hold được khóa theo session_id (không phải user_id) nên khách vãng lai
 * đang xem cùng lúc trên nhiều tab/trình duyệt vẫn cạnh tranh đúng như nhau.
 *
 * Hold hết hạn tự động không còn được tính vào availability (xem
 * AvailabilityService::getBookedQuantity()) — lệnh `room-holds:cleanup` chỉ
 * dọn bảng cho gọn, không phải điều kiện đúng-sai của nghiệp vụ.
 */
class RoomHoldService
{
    public const TTL_MINUTES = 15;

    /**
     * Tạo lại toàn bộ hold của session cho lần "Kiểm tra phòng trống" mới
     * nhất — xóa hold cũ trước vì khách có thể đã đổi phòng/ngày/số lượng.
     *
     * @param  array<int, array{room_type_id: mixed, quantity: int}>  $items
     */
    public function createForSession(string $sessionId, array $items, string $checkIn, string $checkOut): \Illuminate\Support\Carbon
    {
        RoomHold::where('session_id', $sessionId)->delete();

        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        foreach ($items as $item) {
            if (empty($item['room_type_id'])) {
                continue;
            }

            RoomHold::create([
                'room_type_id' => (int) $item['room_type_id'],
                'session_id'   => $sessionId,
                'check_in'     => $checkIn,
                'check_out'    => $checkOut,
                'quantity'     => max(1, (int) ($item['quantity'] ?? 1)),
                'expires_at'   => $expiresAt,
            ]);
        }

        return $expiresAt;
    }

    public function releaseForSession(string $sessionId): void
    {
        RoomHold::where('session_id', $sessionId)->delete();
    }

    public function cleanupExpired(): int
    {
        return RoomHold::where('expires_at', '<', now())->delete();
    }
}
