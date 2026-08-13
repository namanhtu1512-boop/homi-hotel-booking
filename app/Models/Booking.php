<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
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
        'national_id',
        'total_amount',
        'discount_amount',
        'status',
        'deposit_expires_at',
        'expired_pending_check_at',
        'overdue_checkout_notified_at',
        'note',
    ];

    protected $casts = [
        'check_in'                      => 'date',
        'check_out'                     => 'date',
        'adults'                        => 'integer',
        'children'                      => 'integer',
        'infants'                       => 'integer',
        'total_amount'                  => 'decimal:2',
        'discount_amount'               => 'integer',
        'status'                        => BookingStatus::class,
        'deposit_expires_at'            => 'datetime',
        'expired_pending_check_at'      => 'datetime',
        'overdue_checkout_notified_at'  => 'datetime',
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

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->orderBy('created_at');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function roomChangeRequests()
    {
        return $this->hasMany(RoomChangeRequest::class);
    }

    public function extraBedRequests()
    {
        return $this->hasMany(ExtraBedRequest::class);
    }

    /**
     * Yêu cầu giường phụ đang chờ xử lý của đơn này (tối đa 1 tại 1 thời
     * điểm — ExtraBedInventoryService::countAvailable() không tính booking
     * đang pending_consultation vào pool nên không thể phát sinh yêu cầu thứ 2
     * trong lúc yêu cầu đầu chưa resolve).
     */
    public function pendingExtraBedRequest(): ?ExtraBedRequest
    {
        return $this->extraBedRequests->firstWhere('status', 'pending');
    }

    public function earlyCheckinRequests()
    {
        return $this->hasMany(EarlyCheckinRequest::class);
    }

    public function lateCheckoutRequests()
    {
        return $this->hasMany(LateCheckoutRequest::class);
    }

    public function groupDiscountRequests()
    {
        return $this->hasMany(GroupDiscountRequest::class);
    }

    public function incidentalInvoice()
    {
        return $this->hasOne(IncidentalInvoice::class)->latestOfMany();
    }

    /**
     * Hóa đơn phát sinh (dịch vụ/phụ phí thêm trong lúc lưu trú) còn tiền
     * CHƯA thu — dùng để chặn trả phòng cho tới khi lễ tân xác nhận đã thu
     * đủ (xem IncidentalInvoiceService::markPaid(), BookingService::checkOut()).
     * Tách hẳn khỏi tiền phòng gốc (payment) — xem canCheckOut().
     */
    public function hasUnpaidIncidentalInvoice(): bool
    {
        return $this->incidentalInvoice
            && $this->incidentalInvoice->isOpen()
            && (float) $this->incidentalInvoice->total_amount > 0;
    }

    /**
     * Khách LUÔN được tự hủy đơn ở trạng thái phù hợp (PENDING/PENDING_DEPOSIT/
     * CONFIRMED), bất kể còn bao lâu tới giờ nhận phòng — khác trước đây (chặn
     * hẳn nếu hủy trễ). Hậu quả của việc hủy trễ là PHÍ HỦY theo bậc (xem
     * cancellationFeePercent(), BookingService::attemptRefund()) thay vì bị
     * chặn không cho hủy.
     */
    public function canCancelByCustomer(): bool
    {
        return $this->status->canCancelByCustomer();
    }

    /**
     * Phí hủy (% trên tổng tiền đơn) theo số giờ còn lại tới giờ nhận phòng
     * DỰ KIẾN (xem hoursUntilCheckIn()) — càng hủy sát giờ nhận phòng, phí
     * càng cao. Mốc giờ/mức phí cố định trong code (không cấu hình qua
     * admin — khác cancellation_hours_before cũ đã bỏ):
     *
     *   ≥ 48 giờ           → 0%   (miễn phí, hoàn 100%)
     *   24 giờ đến < 48 giờ → 30%  (hoàn 70%)
     *   12 giờ đến < 24 giờ → 50%  (hoàn 50%)
     *   < 12 giờ (kể cả sau giờ nhận phòng/no-show, hoursUntilCheckIn() âm)
     *                        → 100% (không hoàn)
     *
     * Phí này chỉ là TRẦN tối đa — BookingService::attemptRefund() vẫn giới
     * hạn số tiền giữ lại KHÔNG VƯỢT QUÁ số tiền khách đã thực trả
     * (amount_collected), không đòi thêm phần thiếu nếu khách mới chỉ cọc.
     */
    public function cancellationFeePercent(): int
    {
        $hours = $this->hoursUntilCheckIn();

        return match (true) {
            $hours >= 48 => 0,
            $hours >= 24 => 30,
            $hours >= 12 => 50,
            default      => 100,
        };
    }

    /**
     * Đơn còn trong vòng N giờ trước giờ nhận phòng đã đặt mới được gửi yêu
     * cầu nhận phòng sớm — xem EarlyCheckinRequestService::REQUEST_WINDOW_HOURS_BEFORE.
     * Chiều so sánh ngược với canCancelByCustomer() (<= thay vì >=): hủy
     * phải làm SỚM, còn yêu cầu nhận phòng sớm chỉ có ý nghĩa khi đã GẦN tới
     * ngày nhận phòng.
     */
    public function canRequestEarlyCheckin(): bool
    {
        return $this->status === BookingStatus::CONFIRMED
            && $this->hoursUntilCheckIn() <= \App\Services\EarlyCheckinRequestService::REQUEST_WINDOW_HOURS_BEFORE;
    }

    /**
     * Chỉ được gửi yêu cầu trả phòng muộn khi đang lưu trú (đã nhận phòng,
     * chưa trả phòng) — xem LateCheckoutRequestService::create().
     */
    public function canRequestLateCheckout(): bool
    {
        return $this->status === BookingStatus::CHECKED_IN;
    }

    /**
     * Số giờ còn lại tính tới thời điểm nhận phòng DỰ KIẾN (ngày check_in +
     * giờ nhận phòng chuẩn của khách sạn `hotel_info.check_in_time`, mặc
     * định 14:00 nếu chưa cấu hình) — dùng cho canCancelByCustomer() để so
     * với hạn hủy theo giờ (cancellation_hours_before) thay vì chỉ so ngày
     * như trước đây (đặt cùng ngày trước kia luôn hủy được bất kể còn mấy
     * giờ, không đúng với chính sách "phải hủy trước N giờ").
     */
    public function hoursUntilCheckIn(): float
    {
        $checkInTime = HotelInfo::instance()->check_in_time ?? '14:00';

        $expectedCheckIn = \Carbon\Carbon::parse($this->check_in->toDateString() . ' ' . $checkInTime, 'Asia/Ho_Chi_Minh');

        return now('Asia/Ho_Chi_Minh')->diffInHours($expectedCheckIn);
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
     * Hạn chót trả phòng thực tế của đơn: giờ chuẩn `hotel_info.check_out_time`
     * (mặc định 12:00) vào ĐÚNG ngày check_out đã đặt — trừ khi có 1
     * LateCheckoutRequest đã được duyệt thì lấy giờ đã duyệt thay thế (khách
     * xin phép trước thì không tính là "trễ" nữa, xem isOverdueCheckout()).
     */
    public function checkoutDeadline(): \Carbon\Carbon
    {
        $standardTime = substr(HotelInfo::instance()->check_out_time ?? '12:00:00', 0, 5);

        $approved = $this->lateCheckoutRequests()->where('status', 'approved')->latest('handled_at')->first();
        $time = $approved ? substr($approved->requested_checkout_time, 0, 5) : $standardTime;

        return \Carbon\Carbon::parse($this->check_out->toDateString() . ' ' . $time, 'Asia/Ho_Chi_Minh');
    }

    /**
     * Đang lưu trú (CHECKED_IN) mà đã quá hạn trả phòng (xem checkoutDeadline())
     * mà khách vẫn chưa trả phòng — dùng để cảnh báo "trả phòng trễ giờ" ở
     * trang Phòng vật lý (đổi đỏ) và cron BookingService::flagOverdueCheckouts().
     */
    public function isOverdueCheckout(): bool
    {
        return $this->status === BookingStatus::CHECKED_IN
            && now('Asia/Ho_Chi_Minh')->gt($this->checkoutDeadline());
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
        return in_array($this->status, [BookingStatus::PENDING, BookingStatus::PENDING_DEPOSIT, BookingStatus::CONFIRMED, BookingStatus::CHECKED_IN], true)
            && $this->payment
            && $this->payment->status->canTransitionTo(PaymentStatus::PAID);
    }

    /**
     * Đặt cọc 30% áp dụng ngay từ khi đơn còn chờ xác nhận (pending/
     * pending_deposit) tới khi đã xác nhận — miễn CHƯA thanh toán/báo
     * chuyển khoản gì cả (chỉ hợp lệ từ UNPAID — xem
     * PaymentStatus::canTransitionTo()).
     */
    public function canPayDeposit(): bool
    {
        return in_array($this->status, [BookingStatus::PENDING, BookingStatus::PENDING_DEPOSIT, BookingStatus::CONFIRMED], true)
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
     * Gia hạn thời gian thuê phòng chỉ hợp lệ trong thời gian khách đang lưu
     * trú (đã check-in, chưa check-out) — xem BookingService::extendStay().
     */
    public function canExtendStay(): bool
    {
        return $this->status === BookingStatus::CHECKED_IN;
    }

    /**
     * Còn có thể cộng thêm giảm giá đoàn/nhóm (tự động theo chính sách hoặc
     * nhân viên/admin đề xuất thêm) hay không — chặn khi đơn đã hủy/trả
     * phòng/hoàn thành (không còn gì để giảm) hoặc đã thanh toán ĐỦ (PAID),
     * tránh giảm giá sau khi đã thu đủ tiền tạo ra công nợ âm; trường hợp đó
     * nên xử lý hoàn tiền thủ công thay vì qua luồng này.
     */
    public function canApplyGroupDiscount(): bool
    {
        return ! in_array($this->status, [BookingStatus::CANCELLED, BookingStatus::CHECKED_OUT, BookingStatus::COMPLETED], true)
            && $this->payment
            && $this->payment->status !== PaymentStatus::PAID;
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

    /**
     * Tổng tiền phòng đã thực thu tới hiện tại. Ưu tiên payment.amount_collected
     * (ghi khi thu tại quầy hoặc gateway xác nhận); payDepositDemo() không ghi
     * amount_collected khi mới đặt cọc nên fallback về deposit_amount trong
     * trường hợp đó.
     */
    public function paidAmount(): float
    {
        if (! $this->payment) {
            return 0.0;
        }

        // PAID nghĩa là đã thu đủ theo định nghĩa nghiệp vụ — dùng thẳng
        // payment->amount thay vì amount_collected, vì một số đơn cũ được
        // đánh dấu PAID mà không ghi amount_collected (dữ liệu trước khi có
        // updatePaymentStatus() ghi nhận amount_collected cho thanh toán thủ công).
        if ($this->payment->status === PaymentStatus::PAID) {
            return (float) $this->payment->amount;
        }

        $collected = (float) ($this->payment->amount_collected ?? 0);

        if ($collected <= 0 && $this->payment->status === PaymentStatus::DEPOSIT_PAID) {
            $collected = (float) $this->payment->deposit_amount;
        }

        return $collected;
    }

    /**
     * Số tiền phòng còn phải thu (dương) hoặc số dư cần hoàn khách (âm) —
     * có thể âm sau khi đổi sang phòng giá thấp hơn làm total_amount giảm
     * xuống dưới số đã thu (xem RoomChangeRequestService).
     */
    public function amountDue(): float
    {
        return round((float) $this->total_amount - $this->paidAmount());
    }

    /**
     * Tiền phòng đã thu, tách thành từng khoản theo đúng kênh/thời điểm thu —
     * CASH_WITH_DEPOSIT là 2 lượt thu riêng biệt (cọc online lúc đặt phòng,
     * rồi tiền mặt lúc nhận phòng), không phải 1 khoản gộp, nên hoá đơn cần
     * liệt kê riêng thay vì gắn cả total vào tên phương thức tổng quát.
     */
    public function paymentBreakdown(): array
    {
        if (! $this->payment) {
            return [];
        }

        $payment = $this->payment;

        if ($payment->method === PaymentMethod::CASH_WITH_DEPOSIT && (float) $payment->deposit_amount > 0) {
            $rows = [
                ['label' => 'Đặt cọc trực tuyến', 'amount' => (float) $payment->deposit_amount],
            ];

            $cashCollected = round($this->paidAmount() - (float) $payment->deposit_amount);

            if ($cashCollected > 0) {
                $rows[] = ['label' => 'Tiền mặt khi nhận phòng', 'amount' => $cashCollected];
            }

            return $rows;
        }

        $paid = $this->paidAmount();

        return $paid > 0 ? [['label' => $payment->method->label(), 'amount' => $paid]] : [];
    }
}
