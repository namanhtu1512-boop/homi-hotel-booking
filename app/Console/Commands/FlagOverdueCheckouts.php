<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class FlagOverdueCheckouts extends Command
{
    protected $signature   = 'bookings:flag-overdue-checkouts';
    protected $description = 'Phát hiện & thông báo cho admin/staff các đơn quá giờ trả phòng chuẩn mà khách chưa trả phòng';

    public function handle(BookingService $bookingService): int
    {
        $count = $bookingService->flagOverdueCheckouts();

        $this->info("Đã phát hiện và thông báo {$count} đơn quá giờ trả phòng.");

        return self::SUCCESS;
    }
}
