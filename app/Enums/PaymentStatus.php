<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case UNPAID   = 'unpaid';
    case PENDING  = 'pending';
    case PAID     = 'paid';
    case REFUNDED = 'refunded';
    case FAILED   = 'failed';

    public function label(): string
    {
        return match($this) {
            self::UNPAID   => 'Chưa thanh toán',
            self::PENDING  => 'Đang xử lý',
            self::PAID     => 'Đã thanh toán',
            self::REFUNDED => 'Đã hoàn tiền',
            self::FAILED   => 'Thất bại',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    public function canRefund(): bool
    {
        return $this === self::PAID;
    }
}
