<?php

namespace App\Notifications;

use App\Models\GroupDiscountRequest;
use Illuminate\Notifications\Notification;

class NewGroupDiscountRequest extends Notification
{
    public function __construct(public readonly GroupDiscountRequest $groupDiscountRequest) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(): array
    {
        $booking = $this->groupDiscountRequest->booking;

        return [
            'message' => "Đề xuất giảm giá đoàn mới cho đơn {$booking->booking_code} ({$this->groupDiscountRequest->requested_percent}%).",
            'url'     => route('admin.group-discount-requests.show', $this->groupDiscountRequest->id),
        ];
    }
}
