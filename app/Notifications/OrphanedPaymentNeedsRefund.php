<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\RefundRequest;
use Illuminate\Notifications\Notification;

/**
 * VNPay báo thanh toán thành công SAU KHI booking đã bị hủy (hết hạn giữ chỗ
 * + khoảng đệm, hoặc admin hủy tay) — xem BookingService::resolveOrphanedRefund().
 * Gửi cho cả khách (biết tiền đã bị trừ, đang được xử lý) lẫn admin/staff
 * (biết để đối soát, kể cả khi hoàn tiền tự động qua API đã thành công).
 */
class OrphanedPaymentNeedsRefund extends Notification
{
    public function __construct(
        public readonly Booking $booking,
        public readonly RefundRequest $refundRequest,
    ) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isStaff = in_array($notifiable->role ?? null, ['admin', 'staff'], true);
        $amount  = number_format((float) $this->refundRequest->amount, 0, ',', '.');

        if ($isStaff) {
            $routePrefix = $notifiable->role === 'staff' ? 'staff' : 'admin';
            $refundState = match ($this->refundRequest->status->value) {
                'refunded' => 'đã hoàn tiền tự động thành công qua VNPay',
                'failed'   => 'hoàn tiền tự động THẤT BẠI, cần hoàn thủ công',
                default    => 'đang chờ xử lý hoàn tiền',
            };

            return [
                'booking_id'   => $this->booking->id,
                'booking_code' => $this->booking->booking_code,
                'message'      => "⚠️ Đơn {$this->booking->booking_code} đã bị hủy do quá hạn giữ chỗ nhưng VNPay báo thanh toán thành công {$amount}đ sau đó — {$refundState} (yêu cầu hoàn tiền #{$this->refundRequest->id}).",
                'url'          => route("{$routePrefix}.bookings.show", $this->booking->id),
            ];
        }

        return [
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->booking_code,
            'message'      => "Đơn {$this->booking->booking_code} đã hết hạn giữ chỗ và bị hủy trước khi hệ thống kịp ghi nhận thanh toán {$amount}đ của bạn. Chúng tôi đang xử lý hoàn tiền, vui lòng liên hệ hotline nếu cần hỗ trợ thêm.",
            'url'          => route('customer.bookings.show', $this->booking->id),
        ];
    }
}
