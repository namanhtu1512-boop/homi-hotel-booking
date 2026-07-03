<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'user_id',
        'check_in',
        'check_out',
        'nights',
        'customer_name',
        'customer_email',
        'customer_phone',
        'total_amount',
        'status',
        'note',
    ];

    protected $casts = [
        'check_in'     => 'date',
        'check_out'    => 'date',
        'total_amount' => 'decimal:2',
        'status'       => BookingStatus::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookingItems()
    {
        return $this->hasMany(BookingItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(BookingStatusLog::class)->orderBy('created_at');
    }

    public function canCancelByCustomer(): bool
    {
        return $this->status->canCancelByCustomer() && $this->check_in->isAfter(today());
    }

    public function canCancelByAdmin(): bool
    {
        return $this->status->canCancelByAdmin();
    }

    public function canConfirm(): bool
    {
        return $this->status->canConfirm();
    }

    public function canComplete(): bool
    {
        return $this->status->canComplete();
    }

    /**
     * Chỉ được đánh dấu "đã thanh toán" khi đơn đã được admin/staff xác nhận
     * (confirmed) — tránh thu tiền cho đơn còn đang chờ duyệt.
     */
    public function canMarkPaymentAsPaid(): bool
    {
        return $this->status === BookingStatus::CONFIRMED
            && $this->payment
            && $this->payment->status->canTransitionTo(PaymentStatus::PAID);
    }
}
