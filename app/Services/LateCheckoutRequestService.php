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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Khách ĐANG LƯU TRÚ (đã check-in) gửi yêu cầu trả phòng muộn hơn giờ chuẩn
 * của khách sạn, staff/admin kiểm tra tình trạng phòng rồi duyệt hoặc từ
 * chối. Đây là luồng THAY THẾ hoàn toàn phụ phí trả phòng muộn tự động
 * trước đây (xem BookingService::checkOut()) — khách không xin phép trước
 * thì không tự động bị tính phí nữa; staff vẫn có thể cộng phụ phí thủ công
 * qua "Thêm phụ phí phát sinh" như trước nếu cần xử lý ngoại lệ.
 *
 * Phí tính theo bậc cố định (không theo % như nhận phòng sớm) — xem
 * calculateFee().
 */
class LateCheckoutRequestService
{
    /**
     * Phải gửi yêu cầu trước giờ trả phòng CHUẨN của khách sạn ít nhất N giờ
     * — để admin/staff kịp kiểm tra tình trạng phòng (có khách mới sắp nhận
     * phòng này không) trước khi duyệt, tránh khách báo sát giờ mới hỏi.
     */
    public const MIN_HOURS_BEFORE_STANDARD_CHECKOUT = 3;

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
        $requestedTime = $data['requested_checkout_time'];

        $standard = Carbon::createFromFormat('H:i', $standardTime);
        $requested = Carbon::createFromFormat('H:i', $requestedTime);

        if (! $requested->gt($standard)) {
            throw ValidationException::withMessages([
                'requested_checkout_time' => ["Giờ yêu cầu phải muộn hơn giờ trả phòng chuẩn ({$standardTime})."],
            ]);
        }

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

        // absolute=true tường minh — diffInMinutes() mặc định trả giá trị CÓ
        // DẤU từ Carbon 3 trở đi (xem EarlyCheckinRequestService::create()).
        $minutesLate = $requested->diffInMinutes($standard, true);
        $hoursLate = round($minutesLate / 60, 2);
        $isAfterEighteen = $requestedTime >= '18:00';

        // Dùng nightly_total của ĐÊM CUỐI CÙNG trong price_breakdown — cùng
        // quy ước đã dùng cho phụ phí trả phòng muộn tự động trước đây.
        $lastNightTotal = $booking->bookingItems->sum(function (BookingItem $item) {
            $breakdown = $item->price_breakdown ?? [];
            $lastNight = $breakdown !== [] ? ($breakdown[count($breakdown) - 1]['nightly_total'] ?? $item->price_per_night) : $item->price_per_night;

            return (float) $lastNight * $item->quantity;
        });

        $fee = self::calculateFee($hoursLate, $isAfterEighteen, $lastNightTotal);

        $request = LateCheckoutRequest::create([
            'booking_id'               => $booking->id,
            'user_id'                  => $customer->id,
            'requested_checkout_time'  => $requestedTime,
            'hours_late'               => $hoursLate,
            'fee_amount'               => $fee,
            'reason'                   => $data['reason'] ?? null,
            'status'                   => 'pending',
        ]);

        User::whereIn('role', ['admin', 'staff'])->each(
            fn (User $u) => $u->notify(new NewLateCheckoutRequest($request))
        );

        return $request;
    }

    /**
     * Bảng phí cố định theo bậc giờ trễ:
     *   - Đến 1 giờ: 100.000đ
     *   - Trên 1 đến 2 giờ: 200.000đ
     *   - Trên 2 đến 3 giờ: 300.000đ
     *   - Trên 3 đến 6 giờ: 50% giá phòng đêm cuối
     *   - Trên 6 giờ hoặc từ 18:00 trở đi: 100% giá phòng đêm cuối (tính như
     *     thêm 1 đêm)
     * So trực tiếp với mốc giờ thực tế (KHÔNG ceil theo giờ như phí nhận
     * phòng sớm) — VD trễ 1.5 giờ rơi vào bậc "trên 1 đến 2 giờ", không làm
     * tròn lên 2 giờ.
     */
    public static function calculateFee(float $hoursLate, bool $isAfterEighteen, float $lastNightTotal): float
    {
        if ($isAfterEighteen || $hoursLate > 6) {
            return round($lastNightTotal);
        }

        if ($hoursLate > 3) {
            return round($lastNightTotal * 0.5);
        }

        if ($hoursLate > 2) {
            return 300000;
        }

        if ($hoursLate > 1) {
            return 200000;
        }

        return 100000;
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
            $fee = (float) $lateCheckoutRequest->fee_amount;
            $newTime = substr($lateCheckoutRequest->requested_checkout_time, 0, 5);

            $this->incidentalInvoiceService->addItem(
                $booking, 'surcharge', "Phụ phí trả phòng muộn tới {$newTime} (đã duyệt)", $fee
            );

            $lateCheckoutRequest->update([
                'status'     => 'approved',
                'handled_by' => $staff->id,
                'handled_at' => now(),
            ]);

            $feeText = number_format($fee, 0, ',', '.') . 'đ';
            $booking->user?->notify(new BookingStatusChanged(
                $booking,
                "Yêu cầu trả phòng muộn tới {$newTime} cho đơn {$booking->booking_code} đã được duyệt. Phụ phí {$feeText} đã ghi vào hóa đơn phát sinh, thanh toán khi trả phòng."
            ));

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
