<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING         = 'pending';
    case PENDING_DEPOSIT = 'pending_deposit';
    case CONFIRMED       = 'confirmed';
    case CANCELLED       = 'cancelled';
    case CHECKED_IN      = 'checked_in';
    case CHECKED_OUT     = 'checked_out';
    case COMPLETED       = 'completed';

    public function label(): string
    {
        return match($this) {
            self::PENDING         => 'Chờ xác nhận',
            self::PENDING_DEPOSIT => 'Chờ đặt cọc/thanh toán',
            self::CONFIRMED       => 'Đã xác nhận',
            self::CANCELLED       => 'Đã hủy',
            self::CHECKED_IN      => 'Đang lưu trú',
            self::CHECKED_OUT     => 'Đã trả phòng',
            self::COMPLETED       => 'Hoàn thành',
        };
    }

    /**
     * Class màu badge tương ứng (dùng chung với .badge-* trong layout).
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::PENDING, self::PENDING_DEPOSIT => 'badge-orange',
            self::CONFIRMED, self::CHECKED_IN => 'badge-blue',
            self::CHECKED_OUT, self::COMPLETED => 'badge-green',
            self::CANCELLED   => 'badge-red',
        };
    }

    /**
     * Các trạng thái đang "giữ phòng" — dùng để tính availability.
     * pending + pending_deposit + confirmed + checked_in đều chiếm slot
     * phòng — pending_deposit vẫn giữ chỗ trong lúc chờ khách cọc/thanh
     * toán (xem BookingService::DEPOSIT_HOLD_MINUTES), chỉ nhả ra khi
     * chuyển sang confirmed hoặc bị tự hủy (cancelled).
     */
    public static function holdingStatuses(): array
    {
        return [
            self::PENDING->value,
            self::PENDING_DEPOSIT->value,
            self::CONFIRMED->value,
            self::CHECKED_IN->value,
        ];
    }

    public function canCancelByCustomer(): bool
    {
        return in_array($this, [self::PENDING, self::PENDING_DEPOSIT, self::CONFIRMED], true);
    }

    public function canCancelByAdmin(): bool
    {
        // Bao gồm cả CHECKED_IN — khách đã nhận phòng nhưng cần hủy giữa
        // chừng (rời sớm, sự cố...) vẫn phải hủy được, kèm hoàn tiền nếu đã
        // thanh toán đủ (xem BookingService::cancelByAdmin()).
        return in_array($this, [self::PENDING, self::PENDING_DEPOSIT, self::CONFIRMED, self::CHECKED_IN], true);
    }

    public function canConfirm(): bool
    {
        return $this === self::PENDING;
    }

    public function canCheckIn(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function canCheckOut(): bool
    {
        return $this === self::CHECKED_IN;
    }

    /**
     * Admin đánh dấu đơn đã hoàn thành — chỉ hợp lệ từ checked_out, bắt buộc
     * phải qua check-in/check-out phòng thật trước. Trước đây cho phép cả
     * confirmed->completed (luồng rút gọn) nhưng gây lỗi: đơn hoàn thành mà
     * không có số phòng nào được gán (staff bấm nhầm "hoàn thành" thay vì
     * "check-in").
     */
    public function canComplete(): bool
    {
        return $this === self::CHECKED_OUT;
    }
}
