<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dọn room_holds hết hạn (giữ chỗ tạm thời) — chỉ để bảng gọn, không phải
// điều kiện đúng-sai nghiệp vụ (AvailabilityService đã tự loại hold hết hạn).
Schedule::command('room-holds:cleanup')->everyFiveMinutes();

// Tự hủy các đơn "pending_deposit" quá hạn giữ chỗ (BookingService::DEPOSIT_HOLD_MINUTES)
// — đây LÀ điều kiện nghiệp vụ thật (khác room-holds:cleanup ở trên), cần chạy
// sát hơn để không giữ phòng quá lâu ngoài ý muốn sau khi hết hạn.
Schedule::command('bookings:cancel-expired-deposits')->everyMinute();
