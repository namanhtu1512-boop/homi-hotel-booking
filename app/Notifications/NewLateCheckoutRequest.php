<?php

namespace App\Notifications;

use App\Models\LateCheckoutRequest;
use Illuminate\Notifications\Notification;

class NewLateCheckoutRequest extends Notification
{
    public function __construct(public readonly LateCheckoutRequest $lateCheckoutRequest) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $routePrefix = $notifiable->role === 'staff' ? 'staff' : 'admin';

        $booking = $this->lateCheckoutRequest->booking;
        $requestedTime = substr($this->lateCheckoutRequest->requested_checkout_time, 0, 5);

        return [
            'message' => "Yêu cầu trả phòng muộn mới cho đơn {$booking->booking_code} (tới {$requestedTime}).",
            'url'     => route("{$routePrefix}.late-checkout-requests.show", $this->lateCheckoutRequest->id),
        ];
    }
}
