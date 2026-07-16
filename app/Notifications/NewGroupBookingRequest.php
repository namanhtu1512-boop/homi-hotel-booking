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

    public function toArray(): array
    {
        return [
            'message' => "Yêu cầu đặt đoàn mới từ {$this->groupRequest->contact_name} ({$this->groupRequest->group_size} khách).",
            'url'     => route('admin.group-bookings.show', $this->groupRequest->id),
        ];
    }
}
