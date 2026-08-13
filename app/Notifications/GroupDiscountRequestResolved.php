<?php

namespace App\Notifications;

use App\Models\GroupDiscountRequest;
use Illuminate\Notifications\Notification;

class GroupDiscountRequestResolved extends Notification
{
    public function __construct(
        public readonly GroupDiscountRequest $groupDiscountRequest,
        public readonly string $outcome, // 'approved' | 'rejected'
        public readonly bool $wasAdjusted,
    ) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $routePrefix = $notifiable->role === 'staff' ? 'staff' : 'admin';
        $booking = $this->groupDiscountRequest->booking;

        $message = match (true) {
            $this->outcome === 'rejected' => "Đề xuất giảm giá {$this->groupDiscountRequest->requested_percent}% cho đơn {$booking->booking_code} đã bị từ chối.",
            $this->wasAdjusted            => "Đề xuất giảm giá cho đơn {$booking->booking_code} đã được duyệt với mức điều chỉnh {$this->groupDiscountRequest->approved_percent}% (thay vì {$this->groupDiscountRequest->requested_percent}% đề xuất).",
            default                       => "Đề xuất giảm giá {$this->groupDiscountRequest->approved_percent}% cho đơn {$booking->booking_code} đã được duyệt.",
        };

        return [
            'message' => $message,
            'url'     => route("{$routePrefix}.group-discount-requests.show", $this->groupDiscountRequest->id),
        ];
    }
}
