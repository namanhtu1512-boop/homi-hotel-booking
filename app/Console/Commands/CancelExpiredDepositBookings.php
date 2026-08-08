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
        $result = $bookingService->cancelExpiredDepositBookings();

        $this->info("Chuyển {$result['moved_to_grace']} đơn sang chờ xác minh (đệm chờ VNPay), tự hủy hẳn {$result['cancelled']} đơn quá hạn giữ chỗ.");

        return self::SUCCESS;
    }
}
