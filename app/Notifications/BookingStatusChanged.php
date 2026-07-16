<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

class BookingStatusChanged extends Notification
{
    public function __construct(
        public readonly Booking $booking,
        public readonly string $message,
    ) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(): array
    {
        return [
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->booking_code,
            'message'      => $this->message,
            'url'          => route('customer.bookings.show', $this->booking->id),
        ];
    }
}
