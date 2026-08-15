<?php

namespace App\Notifications;

use App\Models\PromotionRequest;
use Illuminate\Notifications\Notification;

class PromotionRequestResolved extends Notification
{
    public function __construct(
        public readonly PromotionRequest $promotionRequest,
        public readonly string $outcome, // 'approved' | 'rejected'
    ) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(): array
    {
        $message = $this->outcome === 'approved'
            ? "Đề xuất mã ưu đãi \"{$this->promotionRequest->code}\" ({$this->promotionRequest->discount_percent}%) đã được duyệt và đang chạy."
            : "Đề xuất mã ưu đãi \"{$this->promotionRequest->code}\" đã bị từ chối.";

        return [
            'message' => $message,
            'url'     => route('staff.group-discount-requests.index'),
        ];
    }
}
