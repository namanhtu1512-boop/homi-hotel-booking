<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PAY_AT_HOTEL      = 'pay_at_hotel';
    case BANK_TRANSFER     = 'bank_transfer';
    case MOMO              = 'momo';
    case CASH_WITH_DEPOSIT = 'cash_with_deposit';

    public function label(): string
    {
        return match($this) {
            self::PAY_AT_HOTEL      => 'Thanh toán tại khách sạn',
            self::BANK_TRANSFER     => 'Chuyển khoản ngân hàng',
            self::MOMO              => 'Ví MoMo',
            self::CASH_WITH_DEPOSIT => 'Tiền mặt khi nhận phòng (đặt cọc 30%)',
        };
    }
}
