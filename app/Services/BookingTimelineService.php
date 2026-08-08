<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Collection;

/**
 * Gộp 3 nguồn log rời rạc của 1 đơn (BookingStatusLog, PaymentStatusLog,
 * AuditLog) thành 1 timeline duy nhất theo thời gian — dùng cho "Nhật ký
 * thao tác" ở trang chi tiết đơn (staff + admin). $booking truyền vào cần
 * đã được eager-load statusLogs.changedBy, payment.statusLogs.changedBy,
 * auditLogs.user (xem BookingService::findForAdmin()) để tránh N+1.
 */
class BookingTimelineService
{
    public function buildTimeline(Booking $booking): Collection
    {
        $statusEntries = $booking->statusLogs->map(fn ($log) => [
            'at'    => $log->created_at,
            'type'  => 'status',
            'actor' => $log->changedBy?->name ?? 'Khách hàng',
            'label' => 'Trạng thái: ' . ($log->from_status?->label() ?? '—') . ' → ' . $log->to_status->label(),
            'note'  => $log->note,
        ]);

        $paymentEntries = ($booking->payment?->statusLogs ?? collect())->map(fn ($log) => [
            'at'    => $log->created_at,
            'type'  => 'payment',
            'actor' => $log->changedBy?->name ?? 'Khách hàng',
            'label' => 'Thanh toán: ' . ($log->from_status?->label() ?? '—') . ' → ' . $log->to_status->label(),
            'note'  => $log->note,
        ]);

        $auditEntries = $booking->auditLogs->map(fn ($log) => [
            'at'    => $log->created_at,
            'type'  => 'audit',
            'actor' => $log->user?->name ?? 'Hệ thống',
            'label' => $log->actionLabel(),
            'note'  => $log->description,
        ]);

        return $statusEntries
            ->concat($paymentEntries)
            ->concat($auditEntries)
            ->sortByDesc('at')
            ->values();
    }
}
