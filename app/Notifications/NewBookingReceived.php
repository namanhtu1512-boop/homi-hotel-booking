<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

class NewBookingReceived extends Notification
{
    public function __construct(public readonly Booking $booking) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(): array
    {
        return [
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->booking_code,
            'message'      => "Đơn đặt phòng mới #{$this->booking->booking_code} từ {$this->booking->customer_name}.",
            'url'          => route('admin.bookings.show', $this->booking->id),
        ];
    }
}
