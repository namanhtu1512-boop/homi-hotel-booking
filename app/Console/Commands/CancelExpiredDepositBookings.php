<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class CancelExpiredDepositBookings extends Command
{
    protected $signature   = 'bookings:cancel-expired-deposits';
    protected $description = 'Tự động hủy các đơn "chờ đặt cọc/thanh toán" đã quá hạn giữ chỗ, nhả phòng lại';

    public function handle(BookingService $bookingService): int
    {
        $cancelled = $bookingService->cancelExpiredDepositBookings();

        $this->info("Đã tự động hủy {$cancelled} đơn quá hạn giữ chỗ.");

        return self::SUCCESS;
    }
}
