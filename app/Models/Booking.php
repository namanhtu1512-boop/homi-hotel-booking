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
        'infants',
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
        'infants'         => 'integer',
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

    /**
     * Chỉ check-out được khi đã thanh toán ĐỦ (PAID) — trước đây trả phòng
     * được bất kể trạng thái thanh toán, nghĩa là phí phát sinh thêm trong
     * lúc lưu trú (BookingService::applyExtraCharge(), mở lại về PENDING
     * khi có phí mới sau khi đã PAID) có thể bị bỏ qua không thu. Bắt buộc
     * thanh toán xong xuôi (kể cả phần phát sinh) ngay tại bước trả phòng.
     */
    public function canCheckOut(): bool
    {
        return $this->status->canCheckOut()
            && $this->payment
            && $this->payment->status === PaymentStatus::PAID;
    }

    /**
     * "Hôm nay" theo giờ Việt Nam (khách sạn vận hành UTC+7, app chạy UTC —
     * xem config/app.php), quy về NGÀY LỊCH thuần túy (không giờ/múi giờ)
     * để so sánh an toàn với `check_out` (cột date, neo theo giờ UTC của
     * app). Không được so sánh trực tiếp today('Asia/Ho_Chi_Minh') (một THỜI
     * ĐIỂM cụ thể — 00:00 giờ VN tương đương 17:00 UTC hôm trước) với cột
     * check_out bằng lt()/diffInDays() — 2 giá trị neo múi giờ khác nhau vẫn
     * cho kết quả sai dù đã "quy đổi" sang giờ VN.
     */
    private static function todayForCheckoutComparison(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromFormat('Y-m-d', now('Asia/Ho_Chi_Minh')->toDateString())->startOfDay();
    }

    /**
     * Khách đang trả phòng SỚM hơn ngày check_out đã đặt hay không — dùng
     * chung với hiển thị nút "Trả phòng sớm" ở admin/staff, tránh định nghĩa
     * "sớm" bị lặp lại và có thể lệch nhau giữa các nơi.
     */
    public function isEarlyCheckoutToday(): bool
    {
        return self::todayForCheckoutComparison()->lt($this->check_out);
    }

    /**
     * Số đêm chưa sử dụng nếu trả phòng ngay hôm nay (chỉ có ý nghĩa khi
     * isEarlyCheckoutToday() là true).
     */
    public function nightsRemainingForEarlyCheckout(): int
    {
        return self::todayForCheckoutComparison()->diffInDays($this->check_out);
    }

    /**
     * Hôm nay có đúng là ngày check_in đã đặt của đơn hay không — dùng để
     * phân biệt "khách đến sớm trong ngày nhận phòng" (đúng ngày, chỉ sớm
     * giờ) với "khách nhận phòng trễ hơn ngày đã đặt" (sai ngày, không phải
     * đến sớm dù giờ nhận phòng thực tế vẫn trước giờ chuẩn của khách sạn).
     */
    public function isCheckInDateToday(): bool
    {
        return self::todayForCheckoutComparison()->isSameDay($this->check_in);
    }

    /**
     * Hôm nay có đúng là ngày check_out đã đặt của đơn hay không — dùng để
     * phân biệt "khách trả phòng muộn TRONG ngày trả phòng đã đặt" (đúng
     * ngày, chỉ trễ giờ, có thể tính phụ phí trả phòng muộn) với "khách trả
     * phòng sớm hơn ngày đã đặt" (xem isEarlyCheckoutToday(), không tính là
     * muộn dù giờ trả phòng thực tế có thể đã qua giờ chuẩn).
     */
    public function isCheckOutDateToday(): bool
    {
        return self::todayForCheckoutComparison()->isSameDay($this->check_out);
    }

    /**
     * Chỉ được đánh dấu "đã thanh toán" khi đơn đã được admin/staff xác nhận
     * (confirmed) hoặc khách đã nhận phòng (checked_in) — tránh thu tiền cho
     * đơn còn đang chờ duyệt, nhưng vẫn cho phép thu nốt tiền mặt còn lại
     * (deposit_paid -> paid) sau khi khách đã check-in.
     */
    public function canMarkPaymentAsPaid(): bool
    {
        return in_array($this->status, [BookingStatus::CONFIRMED, BookingStatus::CHECKED_IN], true)
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
     *
     * Nếu đã thực sự đặt cọc, dùng đúng số tiền cọc ĐÃ CHỐT tại thời điểm đó
     * (payment.deposit_amount) thay vì tính lại 30% của total_amount hiện
     * tại — nếu không, một phụ phí phát sinh SAU khi cọc (applyExtraCharge())
     * làm total_amount tăng lên sẽ khiến số này bị tính lại sai (hụt hơn số
     * tiền mặt thực sự còn phải thu). Trước khi đặt cọc (chưa có deposit_amount
     * lưu), vẫn dùng công thức 30% hiện tại để xem trước.
     */
    public function remainingAfterDeposit(): float
    {
        $depositPaid = (float) ($this->payment->deposit_amount ?? 0);
        $deposit = $depositPaid > 0 ? $depositPaid : $this->depositAmount();

        return round((float) $this->total_amount - $deposit);
    }
}
