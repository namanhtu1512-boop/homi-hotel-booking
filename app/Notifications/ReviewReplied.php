<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Notifications\Notification;

class ReviewReplied extends Notification
{
    public function __construct(public readonly Review $review) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(): array
    {
        return [
            'review_id' => $this->review->id,
            'message'   => 'Đánh giá của bạn đã được phản hồi.',
            'url'       => route('customer.bookings.index'),
        ];
    }
}
