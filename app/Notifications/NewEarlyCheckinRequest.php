<?php

namespace App\Notifications;

use App\Models\EarlyCheckinRequest;
use Illuminate\Notifications\Notification;

class NewEarlyCheckinRequest extends Notification
{
    public function __construct(public readonly EarlyCheckinRequest $earlyCheckinRequest) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $routePrefix = $notifiable->role === 'staff' ? 'staff' : 'admin';

        $booking = $this->earlyCheckinRequest->booking;

        return [
            'message' => "Yêu cầu nhận phòng sớm mới cho đơn {$booking->booking_code} (sớm {$this->earlyCheckinRequest->hours_early} giờ).",
            'url'     => route("{$routePrefix}.early-checkin-requests.show", $this->earlyCheckinRequest->id),
        ];
    }
}
