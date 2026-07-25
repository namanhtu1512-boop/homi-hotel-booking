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

    public function serviceItems()
    {
        return $this->hasMany(BookingServiceItem::class);
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
        // >= hôm nay (không chỉ isAfter) — hệ thống cho phép đặt phòng cùng
        // ngày (DateRangeService::validate), nên khách đặt hôm nay vẫn phải
        // hủy được trước khi thực sự nhận phòng, không bị khóa ngay lập tức.
        return $this->status->canCancelByCustomer() && $this->check_in->gte(today());
    }

    public function canCancelByAdmin(): bool
    {
        return $this->status->canCancelByAdmin();
    }

    public function canConfirm(): bool
    {
        return $this->status->canConfirm();
    }

    /**
     * Chỉ đánh dấu hoàn thành được khi đơn đã thanh toán đủ (PAID) — tránh
     * đơn kết thúc lưu trú mà khách sạn chưa thu đủ tiền vẫn bị chốt sổ.
     */
    public function canComplete(): bool
    {
        return $this->status->canComplete()
            && $this->payment
            && $this->payment->status === PaymentStatus::PAID;
    }

    /**
     * Chỉ check-in được khi đơn đã xác nhận VÀ đã cọc/thanh toán — tránh
     * khách vào phòng mà chưa trả bất kỳ khoản nào (xem báo cáo lỗi:
     * check-in xong là vào phòng luôn không cần thanh toán).
     */
    public function canCheckIn(): bool
    {
        return $this->status->canCheckIn()
            && $this->payment
            && in_array($this->payment->status, [PaymentStatus::DEPOSIT_PAID, PaymentStatus::PAID], true);
    }

    public function canCheckOut(): bool
    {
        return $this->status->canCheckOut();
    }

    /**
     * Khách được thanh toán ngay khi vừa đặt (pending), không cần chờ
     * admin/staff xác nhận trước — nhân viên xác nhận đơn SAU khi khách đã
     * trả tiền (đối soát), thay vì phải xác nhận trước rồi khách mới trả
     * được. Vẫn cho phép thu nốt tiền mặt còn lại (deposit_paid -> paid)
     * sau khi khách đã check-in.
     */
    public function canMarkPaymentAsPaid(): bool
    {
        return in_array($this->status, [BookingStatus::PENDING, BookingStatus::CONFIRMED, BookingStatus::CHECKED_IN], true)
            && $this->payment
            && $this->payment->status->canTransitionTo(PaymentStatus::PAID);
    }

    /**
     * Đặt cọc 30% áp dụng ngay từ khi đơn còn chờ xác nhận (pending) tới khi
     * đã xác nhận — miễn CHƯA thanh toán/báo chuyển khoản gì cả (chỉ hợp lệ
     * từ UNPAID — xem PaymentStatus::canTransitionTo()).
     */
    public function canPayDeposit(): bool
    {
        return in_array($this->status, [BookingStatus::PENDING, BookingStatus::CONFIRMED], true)
            && $this->payment
            && $this->payment->status->canTransitionTo(PaymentStatus::DEPOSIT_PAID);
    }

    /**
     * Thêm dịch vụ tại chỗ chỉ hợp lệ trong thời gian khách đang lưu trú
     * (đã check-in, chưa check-out) — xem BookingService::addServices().
     */
    public function canAddServices(): bool
    {
        return $this->status === BookingStatus::CHECKED_IN;
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
