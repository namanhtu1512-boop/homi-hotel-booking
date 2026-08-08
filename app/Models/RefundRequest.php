<?php

namespace App\Models;

use App\Enums\RefundRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Yêu cầu hoàn tiền cho giao dịch VNPay báo thành công SAU KHI booking đã bị
 * hủy (hết hạn giữ chỗ + khoảng đệm, hoặc admin hủy tay) — xem
 * BookingService::confirmVnpayReturn()/resolveOrphanedRefund(). unique trên
 * payment_id (migration) đảm bảo idempotent nếu VNPay gọi lại IPN nhiều lần.
 */
class RefundRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'payment_id',
        'transaction_code',
        'gateway_transaction_no',
        'amount',
        'reason',
        'status',
        'gateway_response',
        'resolved_at',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'status'           => RefundRequestStatus::class,
        'gateway_response' => 'array',
        'resolved_at'      => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
