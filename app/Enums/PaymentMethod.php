<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PAY_AT_HOTEL  = 'pay_at_hotel';
    case BANK_TRANSFER = 'bank_transfer';
    case ONLINE_DEMO   = 'online_demo';

    public function label(): string
    {
        return match($this) {
            self::PAY_AT_HOTEL  => 'Thanh toán tại khách sạn',
            self::BANK_TRANSFER => 'Chuyển khoản ngân hàng',
            self::ONLINE_DEMO   => 'Thanh toán online (mô phỏng)',
        };
    }
}
