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
