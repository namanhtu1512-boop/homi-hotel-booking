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

// Xử lý các đơn "pending_deposit" quá hạn giữ chỗ (BookingService::DEPOSIT_HOLD_MINUTES)
// — đây LÀ điều kiện nghiệp vụ thật (khác room-holds:cleanup ở trên), cần chạy
// sát hơn để không giữ phòng quá lâu ngoài ý muốn sau khi hết hạn. KHÔNG hủy
// ngay — chuyển qua "expired_pending_check" và giữ thêm khoảng đệm
// (config('services.booking.expired_grace_minutes')) trước khi hủy hẳn, để
// bù trễ IPN/return VNPay tới sau khi hold đã hết hạn (xem
// BookingService::processBookingExpiry()).
Schedule::command('bookings:cancel-expired-deposits')->everyMinute();

// Phát hiện đơn quá giờ trả phòng chuẩn (hoặc giờ đã duyệt trả muộn) mà khách
// CHƯA trả phòng — thông báo chuông cho admin/staff (xem
// BookingService::flagOverdueCheckouts()). 15 phút là đủ: đây là cảnh báo vận
// hành, không phải hành động tự động thu tiền/hủy đơn nên không cần sát phút
// như bookings:cancel-expired-deposits ở trên; dedup qua
// overdue_checkout_notified_at nên chạy lặp lại không spam thông báo trùng.
// Màu đỏ trên trang "Phòng vật lý" KHÔNG phụ thuộc job này — luôn tính
// real-time lúc tải trang (xem RoomService::list()).
Schedule::command('bookings:flag-overdue-checkouts')->everyFifteenMinutes();
