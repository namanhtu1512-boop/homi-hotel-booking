<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

/**
 * Đơn vừa bị hủy (khách hoặc admin/staff) mà còn tiền cần hoàn cho khách,
 * và việc hoàn đó KHÔNG tự động hoàn tất hết qua cổng thanh toán — xem
 * BookingService::notifyRefundNeededIfAny() (dùng chung cho cancelByCustomer()
 * và cancelByAdmin()). Chuyển khoản/tiền mặt luôn cần admin/staff tự tay xử
 * lý hoàn (hệ thống chỉ đánh dấu REFUNDED, không có tiền thật di chuyển qua
 * cổng); VNPay thất bại/thiếu thông tin giao dịch cũng cần xử lý thủ công.
 */
class BookingCancelRefundNeeded extends Notification
{
    public function __construct(
        public readonly Booking $booking,
        public readonly float $amount,
        public readonly bool $refundOk,
    ) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $routePrefix = $notifiable->role === 'staff' ? 'staff' : 'admin';
        $amount      = number_format($this->amount, 0, ',', '.');

        return [
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->booking_code,
            'message'      => "💰 Bạn cần hoàn tiền đơn #{$this->booking->booking_code}: {$amount}đ cho khách.",
            'url'          => route("{$routePrefix}.bookings.show", $this->booking->id),
        ];
    }
}
