<?php

namespace App\Notifications;

use App\Models\ChatMessage;
use Illuminate\Notifications\Notification;

class NewChatMessage extends Notification
{
    public function __construct(public readonly ChatMessage $chatMessage) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $preview = $this->chatMessage->body !== ''
            ? $this->chatMessage->body
            : 'Đã gửi 1 hình ảnh.';

        if (mb_strlen($preview) > 80) {
            $preview = mb_substr($preview, 0, 80) . '...';
        }

        return [
            'message' => "Tin nhắn mới từ {$this->chatMessage->sender->name}: {$preview}",
            'url'     => route('customer.chat.index'),
        ];
    }
}
