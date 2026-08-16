<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\HotelInfo;
use App\Models\LateCheckoutRequest;
use App\Models\User;
use App\Notifications\BookingStatusChanged;
use App\Notifications\NewLateCheckoutRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Khách ĐANG LƯU TRÚ (đã check-in) gửi yêu cầu trả phòng muộn hơn giờ chuẩn
 * của khách sạn. Trễ tối đa self::AUTO_APPROVE_MAX_HOURS (1 giờ): tự động
 * DUYỆT ngay lúc gửi, không cần chờ staff. Trễ hơn thế: vẫn phải chờ
 * staff/admin duyệt dựa trên tình trạng phòng trống thực tế (có khách mới
 * sắp nhận phòng này không) — đối xứng với EarlyCheckinRequestService.
 *
 * Đây là luồng THAY THẾ hoàn toàn phụ phí trả phòng muộn tự động trước đây
 * (xem BookingService::checkOut()) — khách không xin phép trước thì không
 * tự động bị tính phí nữa; staff vẫn có thể cộng phụ phí thủ công qua "Thêm
 * phụ phí phát sinh" như trước nếu cần xử lý ngoại lệ.
 *
 * Phí tính theo % giá phòng đêm cuối, tăng dần theo bậc giờ trễ — xem
 * calculateFee().
 */
class LateCheckoutRequestService
{
    /**
     * Phải gửi yêu cầu trước giờ trả phòng CHUẨN của khách sạn ít nhất N giờ
     * — để admin/staff kịp kiểm tra tình trạng phòng (có khách mới sắp nhận
     * phòng này không) trước khi duyệt, tránh khách báo sát giờ mới hỏi. Áp
     * dụng cho cả 2 nhánh (tự động duyệt lẫn chờ staff) — nhánh tự động vẫn
     * cần khách báo trước 1 khoảng hợp lý, không phải báo ngay lúc đã trễ.
     */
    public const MIN_HOURS_BEFORE_STANDARD_CHECKOUT = 3;

    /**
     * Trễ tối đa bằng số giờ này được tự động duyệt ngay khi gửi yêu cầu,
     * không cần staff can thiệp — trễ hơn phải chờ staff duyệt dựa trên tình
     * trạng phòng trống thực tế. Không áp dụng nếu giờ yêu cầu từ 18:00 trở
     * đi (xem calculateFee()) dù số giờ trễ tính ra vẫn ≤ ngưỡng này.
     */
    public const AUTO_APPROVE_MAX_HOURS = 1;

    public function __construct(
        private readonly IncidentalInvoiceService $incidentalInvoiceService,
    ) {}

    public function create(Booking $booking, User $customer, array $data): LateCheckoutRequest
    {
        if ((int) $booking->user_id !== $customer->id) {
            abort(403);
        }

        if ($booking->status !== BookingStatus::CHECKED_IN) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể yêu cầu trả phòng muộn khi đang lưu trú (đã nhận phòng, chưa trả phòng).'],
            ]);
        }

        if ($booking->lateCheckoutRequests()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'status' => ['Đơn này đang có 1 yêu cầu trả phòng muộn chờ duyệt, vui lòng chờ xử lý xong trước khi gửi yêu cầu mới.'],
            ]);
        }

        $hotel = HotelInfo::instance();
        $standardTime = substr($hotel->check_out_time ?? '12:00:00', 0, 5);

        // Khách chọn SỐ GIỜ muốn trễ (1-6, ràng buộc ở validate() controller)
        // thay vì tự gõ giờ — tránh nhập giờ lệch khỏi đúng bậc phí, đồng thời
        // khớp thẳng với các mốc trong bảng phí calculateFee(). Trên 6 giờ
        // không cho chọn qua form — hướng khách gia hạn hẳn 1 ngày hoặc xuống
        // quầy trao đổi trực tiếp (xem customer.bookings.late-checkout).
        $hoursLate = (int) $data['hours_late'];

        $standard = Carbon::createFromFormat('H:i', $standardTime);
        $requestedTime = (clone $standard)->addHours($hoursLate)->format('H:i');

        // Deadline = giờ chuẩn của ĐÚNG NGÀY trả phòng đã đặt (booking.check_out)
        // — dùng để chặn khách gửi yêu cầu quá sát giờ chuẩn, không đủ thời
        // gian cho khách sạn kiểm tra tình trạng phòng trước khi duyệt. Không
        // truyền absolute — cùng cách làm ở Booking::hoursUntilCheckIn(), âm
        // nghĩa là đã qua giờ chuẩn (luôn bị chặn, đúng ý — không cho xin lùi
        // giờ sau khi đã trễ).
        $deadline = Carbon::parse($booking->check_out->toDateString() . ' ' . $standardTime, 'Asia/Ho_Chi_Minh');

        if (now('Asia/Ho_Chi_Minh')->diffInHours($deadline) < self::MIN_HOURS_BEFORE_STANDARD_CHECKOUT) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể gửi yêu cầu trả phòng muộn trước giờ trả phòng chuẩn ít nhất ' . self::MIN_HOURS_BEFORE_STANDARD_CHECKOUT . ' giờ.'],
            ]);
        }

        $isAfterEighteen = $requestedTime >= '18:00';
        $fee = self::calculateFee($hoursLate, $isAfterEighteen, self::lastNightTotal($booking));

        $autoApprove = $hoursLate <= self::AUTO_APPROVE_MAX_HOURS && ! $isAfterEighteen;

        $attributes = [
            'booking_id'               => $booking->id,
            'user_id'                  => $customer->id,
            'requested_checkout_time'  => $requestedTime,
            'hours_late'               => $hoursLate,
            'fee_amount'               => $fee,
            'reason'                   => $data['reason'] ?? null,
            'status'                   => $autoApprove ? 'approved' : 'pending',
            'handled_at'               => $autoApprove ? now() : null,
        ];

        if (! $autoApprove) {
            $request = LateCheckoutRequest::create($attributes);

            User::whereIn('role', ['admin', 'staff'])->each(
                fn (User $u) => $u->notify(new NewLateCheckoutRequest($request))
            );

            return $request;
        }

        return DB::transaction(function () use ($attributes, $booking) {
            $request = LateCheckoutRequest::create($attributes);

            $this->grantLateCheckout($request, $booking, ' Yêu cầu trả phòng muộn trong vòng ' . self::AUTO_APPROVE_MAX_HOURS . ' giờ được tự động duyệt.');

            return $request;
        });
    }

    /**
     * Cộng phụ phí trả phòng muộn vào hóa đơn phát sinh + báo khách — dùng
     * chung cho cả nhánh tự động duyệt (create(), trễ ≤ AUTO_APPROVE_MAX_HOURS)
     * và nhánh staff duyệt tay (approve()).
     */
    private function grantLateCheckout(LateCheckoutRequest $request, Booking $booking, string $extraNote = ''): void
    {
        $fee = (float) $request->fee_amount;
        $newTime = substr($request->requested_checkout_time, 0, 5);
        // Luôn là số nguyên (1-6) vì form chỉ cho chọn theo giờ tròn — xem
        // create(). Ghi rõ số giờ trễ trong mô tả để khách/staff/admin nhìn
        // hóa đơn phát sinh là hiểu ngay, không phải tự trừ giờ chuẩn.
        $hoursLate = (int) $request->hours_late;

        $this->incidentalInvoiceService->addItem(
            $booking, 'surcharge', "Phụ phí trả phòng muộn {$hoursLate} giờ (tới {$newTime}, đã duyệt)", $fee
        );

        $feeText = number_format($fee, 0, ',', '.') . 'đ';
        $booking->user?->notify(new BookingStatusChanged(
            $booking,
            "Yêu cầu trả phòng muộn {$hoursLate} giờ (tới {$newTime}) cho đơn {$booking->booking_code} đã được duyệt. Phụ phí {$feeText} đã ghi vào hóa đơn phát sinh, thanh toán khi trả phòng.{$extraNote}"
        ));
    }

    /**
     * Phí = % giá phòng đêm cuối, tăng dần theo bậc giờ trễ:
     *   - Đến 2 giờ: 30% giá phòng
     *   - Trên 2 đến 5 giờ: 50% giá phòng
     *   - Trên 5 giờ (kể cả đúng 6 giờ) hoặc từ 18:00 trở đi: 100% giá phòng
     *     (tính như thêm 1 đêm). Form chọn tới 6 giờ vẫn gửi được bình
     *     thường (chờ staff duyệt) — trên 6 giờ mới bắt xuống quầy trao đổi
     *     trực tiếp, khuyến nghị gia hạn hẳn 1 ngày thay vì trả phòng muộn
     *     (xem trang yêu cầu trả phòng muộn).
     * So trực tiếp với mốc giờ thực tế (KHÔNG ceil theo giờ như phí nhận
     * phòng sớm) — VD trễ 1.5 giờ rơi vào bậc "đến 2 giờ", không làm tròn
     * lên 2 giờ.
     */
    public static function calculateFee(float $hoursLate, bool $isAfterEighteen, float $lastNightTotal): float
    {
        if ($isAfterEighteen || $hoursLate > 5) {
            return round($lastNightTotal);
        }

        if ($hoursLate > 2) {
            return round($lastNightTotal * 0.5);
        }

        return round($lastNightTotal * 0.3);
    }

    /**
     * Giá đêm cuối cùng trong price_breakdown của đơn — dùng làm cơ sở tính
     * % phụ phí trả phòng muộn ở cả create() (khách xin phép trước), trang
     * xem trước phí (customer.bookings.late-checkout) và
     * BookingService::applyLateCheckoutSurchargeIfNeeded() (phí tự động dự
     * phòng khi khách không xin phép trước) — dùng chung 1 chỗ để 3 nơi luôn
     * ra cùng 1 kết quả.
     */
    public static function lastNightTotal(Booking $booking): float
    {
        return $booking->bookingItems->sum(function (BookingItem $item) {
            $breakdown = $item->price_breakdown ?? [];
            $lastNight = $breakdown !== [] ? (end($breakdown)['nightly_total'] ?? $item->price_per_night) : $item->price_per_night;

            return (float) $lastNight * $item->quantity;
        });
    }

    public function adminList(array $filters = []): LengthAwarePaginator
    {
        $query = LateCheckoutRequest::with(['booking', 'user'])->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * Danh sách phòng đang trả phòng muộn trong 1 ngày cụ thể — chỉ tính các
     * yêu cầu ĐÃ DUYỆT có booking.check_out đúng ngày đó, loại đơn đã hủy.
     * Trả về 1 dòng cho mỗi booking_item của đơn — xem
     * EarlyCheckinRequestService::usageOnDate() (cùng quy ước).
     */
    public function usageOnDate(string $date): Collection
    {
        return LateCheckoutRequest::with(['booking.bookingItems.roomType', 'booking.bookingItems.rooms'])
            ->where('status', 'approved')
            ->whereHas('booking', fn ($q) => $q
                ->whereDate('check_out', $date)
                ->where('status', '!=', BookingStatus::CANCELLED->value)
            )
            ->get()
            ->flatMap(fn (LateCheckoutRequest $request) => $request->booking->bookingItems->map(fn ($item) => [
                'request'     => $request,
                'booking'     => $request->booking,
                'bookingItem' => $item,
            ]))
            ->values();
    }

    /**
     * @return array{request: LateCheckoutRequest, booking: Booking}
     */
    public function approve(LateCheckoutRequest $lateCheckoutRequest, User $staff): array
    {
        if (! $lateCheckoutRequest->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Yêu cầu này đã được xử lý trước đó.'],
            ]);
        }

        $booking = $lateCheckoutRequest->booking()->with('payment')->firstOrFail();

        if ($booking->status !== BookingStatus::CHECKED_IN) {
            throw ValidationException::withMessages([
                'status' => ['Đơn không còn ở trạng thái đang lưu trú, không thể duyệt yêu cầu trả phòng muộn.'],
            ]);
        }

        return DB::transaction(function () use ($lateCheckoutRequest, $booking, $staff) {
            $lateCheckoutRequest->update([
                'status'     => 'approved',
                'handled_by' => $staff->id,
                'handled_at' => now(),
            ]);

            $this->grantLateCheckout($lateCheckoutRequest, $booking);

            return [
                'request' => $lateCheckoutRequest->fresh(),
                'booking' => $booking->fresh(['payment', 'incidentalInvoice.items']),
            ];
        });
    }

    public function reject(LateCheckoutRequest $lateCheckoutRequest, User $staff, ?string $note): LateCheckoutRequest
    {
        if (! $lateCheckoutRequest->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Yêu cầu này đã được xử lý trước đó.'],
            ]);
        }

        $lateCheckoutRequest->update([
            'status'     => 'rejected',
            'staff_note' => $note,
            'handled_by' => $staff->id,
            'handled_at' => now(),
        ]);

        $booking = $lateCheckoutRequest->booking;
        $message = "Yêu cầu trả phòng muộn cho đơn {$booking->booking_code} đã bị từ chối." . ($note ? " Lý do: {$note}" : '') . ' Vui lòng trả phòng đúng giờ chuẩn hoặc liên hệ khách sạn nếu cần hỗ trợ thêm.';
        $booking->user?->notify(new BookingStatusChanged($booking, $message));

        return $lateCheckoutRequest->fresh();
    }
}
