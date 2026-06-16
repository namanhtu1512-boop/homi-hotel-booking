<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING     = 'pending';
    case CONFIRMED   = 'confirmed';
    case CANCELLED   = 'cancelled';
    case CHECKED_IN  = 'checked_in';
    case CHECKED_OUT = 'checked_out';
    case COMPLETED   = 'completed';

    public function label(): string
    {
        return match($this) {
            self::PENDING     => 'Chờ xác nhận',
            self::CONFIRMED   => 'Đã xác nhận',
            self::CANCELLED   => 'Đã hủy',
            self::CHECKED_IN  => 'Đang lưu trú',
            self::CHECKED_OUT => 'Đã trả phòng',
            self::COMPLETED   => 'Hoàn thành',
        };
    }

    /**
     * Các trạng thái đang "giữ phòng" — dùng để tính availability.
     * pending + confirmed + checked_in đều chiếm slot phòng.
     */
    public static function holdingStatuses(): array
    {
        return [
            self::PENDING->value,
            self::CONFIRMED->value,
            self::CHECKED_IN->value,
        ];
    }

    public function canCancelByCustomer(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
    }

    public function canCancelByAdmin(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
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
}
