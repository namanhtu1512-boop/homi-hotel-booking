<?php

namespace App\Notifications;

use App\Models\RoomChangeRequest;
use Illuminate\Notifications\Notification;

class NewRoomChangeRequest extends Notification
{
    public function __construct(public readonly RoomChangeRequest $roomChangeRequest) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $routePrefix = $notifiable->role === 'staff' ? 'staff' : 'admin';

        $booking = $this->roomChangeRequest->booking;

        return [
            'message' => "Yêu cầu đổi phòng mới cho đơn {$booking->booking_code}.",
            'url'     => route("{$routePrefix}.room-change-requests.show", $this->roomChangeRequest->id),
        ];
    }
}
