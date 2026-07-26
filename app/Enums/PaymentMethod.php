<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PAY_AT_HOTEL      = 'pay_at_hotel';
    case BANK_TRANSFER     = 'bank_transfer';
    case CASH_WITH_DEPOSIT = 'cash_with_deposit';
    case ONLINE_VNPAY      = 'online_vnpay';

    public function label(): string
    {
        return match($this) {
            self::PAY_AT_HOTEL      => 'Thanh toán tại khách sạn',
            self::BANK_TRANSFER     => 'Chuyển khoản ngân hàng',
            self::CASH_WITH_DEPOSIT => 'Tiền mặt khi nhận phòng (đặt cọc 30%)',
            self::ONLINE_VNPAY      => 'Thanh toán online qua VNPay',
        };
    }
}
