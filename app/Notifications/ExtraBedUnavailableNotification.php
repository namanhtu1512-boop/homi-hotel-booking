<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\ExtraBedRequest;
use Illuminate\Notifications\Notification;

class ExtraBedUnavailableNotification extends Notification
{
    public function __construct(
        public readonly Booking $booking,
        public readonly ExtraBedRequest $extraBedRequest,
    ) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $routePrefix = $notifiable->role === 'staff' ? 'staff' : 'admin';
        $roomType    = $this->extraBedRequest->bookingItem?->roomType?->name
            ?? $this->booking->bookingItems->first()?->roomType?->name
            ?? 'N/A';
        $thieu = $this->extraBedRequest->requested_extra_beds - $this->extraBedRequest->available_extra_beds;

        return [
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->booking_code,
            'message'      => "Đơn {$this->booking->booking_code} ({$this->booking->customer_name}, "
                . "phòng {$roomType}, {$this->booking->check_in->format('d/m')}"
                . "–{$this->booking->check_out->format('d/m')}) thiếu {$thieu} giường phụ — cần tư vấn khách.",
            'url'          => route("{$routePrefix}.extra-bed-requests.show", $this->extraBedRequest->id),
        ];
    }
}
