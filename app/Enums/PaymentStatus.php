<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case UNPAID       = 'unpaid';
    case PENDING      = 'pending';
    case DEPOSIT_PAID = 'deposit_paid';
    case PAID         = 'paid';
    case REFUNDED     = 'refunded';
    case FAILED       = 'failed';

    public function label(): string
    {
        return match($this) {
            self::UNPAID       => 'Chưa thanh toán',
            self::PENDING      => 'Đang xử lý',
            self::DEPOSIT_PAID => 'Đã đặt cọc 30%',
            self::PAID         => 'Đã thanh toán',
            self::REFUNDED     => 'Đã hoàn tiền',
            self::FAILED       => 'Thất bại',
        };
    }

    /**
     * Class màu badge tương ứng (dùng chung với .badge-* trong layout).
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::UNPAID, self::PENDING => 'badge-orange',
            self::DEPOSIT_PAID => 'badge-blue',
            self::PAID     => 'badge-green',
            self::REFUNDED => 'badge-blue',
            self::FAILED   => 'badge-red',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    /**
     * Đặt cọc không được hoàn tự động khi hủy đơn (chính sách "cọc giữ chỗ")
     * — chỉ khoản đã thanh toán đủ (PAID) mới tự động hoàn khi admin hủy đơn.
     */
    public function canRefund(): bool
    {
        return $this === self::PAID;
    }

    /**
     * Các hướng chuyển trạng thái hợp lệ:
     * unpaid -> paid (thanh toán tại quầy/online) hoặc -> pending (khách tự
     * báo đã chuyển khoản, chờ admin xác nhận) hoặc -> deposit_paid (khách
     * đặt cọc 30% online, phần còn lại trả tiền mặt khi nhận phòng);
     * pending -> paid (admin xác nhận chuyển khoản);
     * deposit_paid -> paid (admin/staff thu đủ tiền mặt còn lại lúc check-in);
     * paid -> refunded (hủy đơn đã trả).
     */
    public function canTransitionTo(self $target): bool
    {
        return match($this) {
            self::UNPAID       => in_array($target, [self::PAID, self::PENDING, self::DEPOSIT_PAID], true),
            self::PENDING      => $target === self::PAID,
            self::DEPOSIT_PAID => $target === self::PAID,
            self::PAID         => $target === self::REFUNDED,
            default => false,
        };
    }
}
