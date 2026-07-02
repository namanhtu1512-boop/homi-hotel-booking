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

    /**
     * Class màu badge tương ứng (dùng chung với .badge-* trong layout).
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::UNPAID, self::PENDING => 'badge-orange',
            self::PAID     => 'badge-green',
            self::REFUNDED => 'badge-blue',
            self::FAILED   => 'badge-red',
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

    /**
     * Admin chỉ được đổi thanh toán theo 2 hướng hợp lệ:
     * unpaid/pending -> paid (khách thanh toán tại quầy), paid -> refunded (hủy đơn đã trả).
     */
    public function canTransitionTo(self $target): bool
    {
        return match($this) {
            self::UNPAID, self::PENDING => $target === self::PAID,
            self::PAID => $target === self::REFUNDED,
            default => false,
        };
    }
}
