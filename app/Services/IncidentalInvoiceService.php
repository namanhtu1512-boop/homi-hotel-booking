<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\IncidentalInvoice;
use App\Models\IncidentalInvoiceItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * "Hóa đơn phát sinh" — gom mọi khoản phát sinh trong lúc lưu trú (dịch vụ
 * thêm, phụ phí thủ công, phí nhận phòng sớm/trả phòng muộn) tách HẲN khỏi
 * tiền phòng gốc (Payment). Tiền phòng đã thanh toán thì không bao giờ bị
 * đụng lại nữa — mọi phát sinh dồn vào đây, chỉ thu 1 lần lúc trả phòng
 * (xem BookingService::checkOut()).
 */
class IncidentalInvoiceService
{
    public function addItem(Booking $booking, string $type, string $description, float $amount, ?int $bookingServiceId = null): IncidentalInvoiceItem
    {
        return DB::transaction(function () use ($booking, $type, $description, $amount, $bookingServiceId) {
            $invoice = $booking->incidentalInvoice()->where('status', 'open')->first()
                ?? IncidentalInvoice::create(['booking_id' => $booking->id, 'status' => 'open']);

            $item = $invoice->items()->create([
                'type'               => $type,
                'description'        => $description,
                'amount'             => $amount,
                'booking_service_id' => $bookingServiceId,
                'created_by'         => Auth::id(),
            ]);

            $invoice->increment('total_amount', $amount);

            return $item;
        });
    }

    public function markPaid(Booking $booking, User $staff): ?IncidentalInvoice
    {
        $invoice = $booking->incidentalInvoice;

        if (! $invoice || ! $invoice->isOpen()) {
            return null;
        }

        $invoice->update([
            'status'       => 'paid',
            'paid_at'      => now(),
            'collected_by' => $staff->id,
        ]);

        return $invoice->fresh();
    }
}
