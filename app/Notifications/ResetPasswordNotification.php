<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(public readonly string $token) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expireMinutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

        return (new MailMessage)
            ->subject('Yêu cầu đặt lại mật khẩu — Homi Hotel')
            ->greeting('Xin chào,')
            ->line('Bạn (hoặc ai đó) vừa yêu cầu đặt lại mật khẩu cho tài khoản Homi của bạn.')
            ->action('Đặt lại mật khẩu', $url)
            ->line("Liên kết này sẽ hết hạn sau {$expireMinutes} phút.")
            ->line('Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.');
    }
}
