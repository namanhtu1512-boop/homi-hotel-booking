<?php

namespace App\Enums;

/**
 * Phân biệt 3 form phụ phí khác hẳn nhau về bản chất (không được gộp
 * chung "hỏng/mất đồ" với "vi phạm" hay "vệ sinh đặc biệt" — xem yêu cầu
 * phân loại 4 form của Homi). "Dịch vụ phát sinh" KHÔNG nằm trong enum
 * này vì nó dùng catalog Service riêng, không phải SurchargeItem.
 */
enum SurchargeCategory: string
{
    case Damage = 'damage';
    case Violation = 'violation';
    case Cleaning = 'cleaning';

    public function label(): string
    {
        return match ($this) {
            self::Damage => 'Hỏng / mất đồ',
            self::Violation => 'Vi phạm quy định',
            self::Cleaning => 'Vệ sinh đặc biệt',
        };
    }
}
