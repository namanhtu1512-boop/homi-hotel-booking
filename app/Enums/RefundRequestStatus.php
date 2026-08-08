<?php

namespace App\Enums;

enum RefundRequestStatus: string
{
    case PENDING  = 'pending';
    case REFUNDED = 'refunded';
    case FAILED   = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'Chờ xử lý',
            self::REFUNDED => 'Đã hoàn tiền',
            self::FAILED   => 'Hoàn tiền thất bại — cần xử lý thủ công',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING  => 'badge-orange',
            self::REFUNDED => 'badge-green',
            self::FAILED   => 'badge-red',
        };
    }
}
