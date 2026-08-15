<?php

namespace App\Notifications;

use App\Models\PromotionRequest;
use Illuminate\Notifications\Notification;

class NewPromotionRequest extends Notification
{
    public function __construct(public readonly PromotionRequest $promotionRequest) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(): array
    {
        $staffName = $this->promotionRequest->user->name ?? 'Nhân viên';

        return [
            'message' => "{$staffName} đề xuất mã ưu đãi khách quen mới \"{$this->promotionRequest->code}\" ({$this->promotionRequest->discount_percent}%).",
            'url'     => route('admin.promotions.index'),
        ];
    }
}
