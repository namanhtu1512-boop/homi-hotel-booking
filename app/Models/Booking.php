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
        'promotion_id',
        'check_in',
        'check_out',
        'nights',
        'adults',
        'children',
        'customer_name',
        'customer_email',
        'customer_phone',
        'total_amount',
        'discount_amount',
        'status',
        'note',
    ];

    protected $casts = [
        'check_in'        => 'date',
        'check_out'       => 'date',
        'adults'          => 'integer',
        'children'        => 'integer',
        'total_amount'    => 'decimal:2',
        'discount_amount' => 'integer',
        'status'          => BookingStatus::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * Toàn bộ mã khuyến mãi đã áp dụng (hỗ trợ stack nhiều mã) — cột
     * promotion_id/discount_amount ở trên chỉ giữ lại mã đầu tiên/tổng giảm
     * để hiển thị nhanh, không phản ánh đầy đủ khi có nhiều hơn 1 mã.
     */
    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'booking_promotions')
            ->withPivot('discount_amount')
            ->withTimestamps();
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

    public function reviews()
    {
        return $this->hasMany(Review::class);
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

    /**
     * Đặt cọc 30% chỉ áp dụng khi đơn đã xác nhận và CHƯA thanh toán/báo
     * chuyển khoản gì cả (chỉ hợp lệ từ UNPAID — xem PaymentStatus::canTransitionTo()).
     */
    public function canPayDeposit(): bool
    {
        return $this->status === BookingStatus::CONFIRMED
            && $this->payment
            && $this->payment->status->canTransitionTo(PaymentStatus::DEPOSIT_PAID);
    }

    /**
     * Số tiền cọc (30% tổng đơn), làm tròn tới đồng.
     */
    public function depositAmount(): float
    {
        return round((float) $this->total_amount * 0.3);
    }

    /**
     * Phần còn lại khách trả bằng tiền mặt khi nhận phòng sau khi đã cọc.
     */
    public function remainingAfterDeposit(): float
    {
        return round((float) $this->total_amount - $this->depositAmount());
    }
}
