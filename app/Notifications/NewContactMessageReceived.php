<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Notifications\Notification;

class NewContactMessageReceived extends Notification
{
    public function __construct(public readonly ContactMessage $contactMessage) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Trang quản lý liên hệ hiện chỉ có ở khu vực admin — staff chưa có
        // route/view riêng cho contact-messages (khác với bookings/group-bookings),
        // nên không cần role-aware route prefix như NewBookingReceived.
        return [
            'contact_message_id' => $this->contactMessage->id,
            'message'            => "Liên hệ mới từ {$this->contactMessage->name}.",
            'url'                => route('admin.contact-messages.index'),
        ];
    }
}
