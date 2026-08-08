<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

class OverdueCheckout extends Notification
{
    public function __construct(public readonly Booking $booking) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $routePrefix = $notifiable->role === 'staff' ? 'staff' : 'admin';

        return [
            'message' => "Đơn {$this->booking->booking_code} đã quá giờ trả phòng chuẩn nhưng khách chưa trả phòng.",
            'url'     => route("{$routePrefix}.bookings.show", $this->booking->id),
        ];
    }
}
