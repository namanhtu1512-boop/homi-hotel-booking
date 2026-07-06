<?php

namespace App\Console\Commands;

use App\Services\RoomHoldService;
use Illuminate\Console\Command;

class CleanupExpiredRoomHolds extends Command
{
    protected $signature   = 'room-holds:cleanup';
    protected $description = 'Xóa các room hold (giữ chỗ tạm thời) đã hết hạn';

    public function handle(RoomHoldService $roomHoldService): int
    {
        $deleted = $roomHoldService->cleanupExpired();

        $this->info("Đã xóa {$deleted} room hold hết hạn.");

        return self::SUCCESS;
    }
}
