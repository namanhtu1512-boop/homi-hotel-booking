<?php

namespace App\Notifications;

use App\Models\GroupBookingRequest;
use Illuminate\Notifications\Notification;

class NewGroupBookingRequest extends Notification
{
    public function __construct(public readonly GroupBookingRequest $groupRequest) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $routePrefix = $notifiable->role === 'staff' ? 'staff' : 'admin';

        return [
            'message' => "Yêu cầu đặt đoàn mới từ {$this->groupRequest->contact_name} ({$this->groupRequest->group_size} khách).",
            'url'     => route("{$routePrefix}.group-bookings.show", $this->groupRequest->id),
        ];
    }
}
