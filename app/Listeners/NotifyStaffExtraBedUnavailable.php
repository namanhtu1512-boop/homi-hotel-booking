<?php

namespace App\Listeners;

use App\Events\ExtraBedUnavailable;
use App\Models\User;
use App\Notifications\ExtraBedUnavailableNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * implements ShouldQueue -> đẩy vào bảng `jobs` (QUEUE_CONNECTION=database
 * trong .env), cần `php artisan queue:work` đang chạy. Chạy độc lập với
 * SuggestAlternativesToCustomer (đồng bộ) — không nhánh nào chặn nhánh kia.
 */
class NotifyStaffExtraBedUnavailable implements ShouldQueue
{
    public function handle(ExtraBedUnavailable $event): void
    {
        User::whereIn('role', ['admin', 'staff'])->each(
            fn (User $u) => $u->notify(new ExtraBedUnavailableNotification(
                $event->booking,
                $event->extraBedRequest,
            ))
        );
    }
}
