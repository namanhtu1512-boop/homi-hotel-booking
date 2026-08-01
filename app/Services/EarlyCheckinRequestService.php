<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\EarlyCheckinRequest;
use App\Models\HotelInfo;
use App\Models\User;
use App\Notifications\BookingStatusChanged;
use App\Notifications\NewEarlyCheckinRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Khách gửi yêu cầu nhận phòng sớm hơn giờ chuẩn của khách sạn (tối đa 3 giờ),
 * staff/admin duyệt hoặc từ chối. Đây là luồng RIÊNG, độc lập với phụ phí nhận
 * phòng sớm tự động (% giá phòng, không cần duyệt) đã có sẵn ở
 * BookingService::checkIn()/applyEarlyCheckinSurchargeIfNeeded() — không đụng
 * vào cơ chế đó.
 *
 * Phí cố định 100.000đ/giờ sớm (làm tròn lên), ghi vào "hóa đơn phát sinh"
 * riêng (IncidentalInvoiceService) — hoàn toàn KHÔNG đụng payment (tiền
 * phòng gốc), nên không cần lo việc duyệt trước khi nhận phòng có thể vô
 * tình chặn check-in — chỉ bắt buộc thanh toán khi trả phòng
 * (BookingService::checkOut() tự thu toàn bộ hóa đơn phát sinh 1 lần).
 */
class EarlyCheckinRequestService
{
    public const FEE_PER_HOUR = 100000;

    public const MAX_HOURS_EARLY = 3;

    /**
     * Khách chỉ được gửi yêu cầu nhận phòng sớm khi còn trong vòng N giờ
     * trước giờ nhận phòng đã đặt — tránh gửi yêu cầu quá sớm (VD ngay lúc
     * vừa đặt phòng, còn vài ngày nữa mới tới) rồi quên mất, khách sạn khó
     * chủ động sắp xếp phòng trước quá lâu. Cùng cơ chế với
     * Booking::canCancelByCustomer() (dùng hoursUntilCheckIn()), chỉ khác
     * chiều so sánh (<=  thay vì  >=).
     */
    public const REQUEST_WINDOW_HOURS_BEFORE = 20;

    public function __construct(
        private readonly IncidentalInvoiceService $incidentalInvoiceService,
    ) {}

    public function create(Booking $booking, User $customer, array $data): EarlyCheckinRequest
    {
        if ((int) $booking->user_id !== $customer->id) {
            abort(403);
        }

        if ($booking->status !== BookingStatus::CONFIRMED) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể yêu cầu nhận phòng sớm khi đơn đã được xác nhận (chưa nhận phòng).'],
            ]);
        }

        if ($booking->hoursUntilCheckIn() > self::REQUEST_WINDOW_HOURS_BEFORE) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể gửi yêu cầu nhận phòng sớm trong vòng ' . self::REQUEST_WINDOW_HOURS_BEFORE . ' giờ trước giờ nhận phòng, vui lòng quay lại gần ngày nhận phòng hơn.'],
            ]);
        }

        if ($booking->earlyCheckinRequests()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'status' => ['Đơn này đang có 1 yêu cầu nhận phòng sớm chờ duyệt, vui lòng chờ xử lý xong trước khi gửi yêu cầu mới.'],
            ]);
        }

        $standardTime = substr(HotelInfo::instance()->check_in_time ?? '14:00:00', 0, 5);
        $requestedTime = $data['requested_arrival_time'];

        $standard = Carbon::createFromFormat('H:i', $standardTime);
        $requested = Carbon::createFromFormat('H:i', $requestedTime);

        if (! $requested->lt($standard)) {
            throw ValidationException::withMessages([
                'requested_arrival_time' => ["Giờ yêu cầu phải sớm hơn giờ nhận phòng chuẩn ({$standardTime})."],
            ]);
        }

        // absolute=true tường minh — diffInMinutes() mặc định trả giá trị
        // CÓ DẤU từ Carbon 3 trở đi (xem BookingService::applyLateCheckoutSurchargeIfNeeded()).
        $minutesEarly = $requested->diffInMinutes($standard, true);
        $hoursEarly = (int) ceil($minutesEarly / 60);

        if ($hoursEarly > self::MAX_HOURS_EARLY) {
            throw ValidationException::withMessages([
                'requested_arrival_time' => ['Chỉ hỗ trợ nhận phòng sớm tối đa ' . self::MAX_HOURS_EARLY . ' giờ trước giờ chuẩn, vui lòng liên hệ khách sạn nếu cần sớm hơn.'],
            ]);
        }

        $request = EarlyCheckinRequest::create([
            'booking_id'              => $booking->id,
            'user_id'                 => $customer->id,
            'requested_arrival_time'  => $requestedTime,
            'hours_early'             => $hoursEarly,
            'fee_amount'              => $hoursEarly * self::FEE_PER_HOUR,
            'reason'                  => $data['reason'] ?? null,
            'status'                  => 'pending',
        ]);

        User::whereIn('role', ['admin', 'staff'])->each(
            fn (User $u) => $u->notify(new NewEarlyCheckinRequest($request))
        );

        return $request;
    }

    public function adminList(array $filters = []): LengthAwarePaginator
    {
        $query = EarlyCheckinRequest::with(['booking', 'user'])->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * @return array{request: EarlyCheckinRequest, booking: Booking}
     */
    public function approve(EarlyCheckinRequest $earlyCheckinRequest, User $staff): array
    {
        if (! $earlyCheckinRequest->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Yêu cầu này đã được xử lý trước đó.'],
            ]);
        }

        $booking = $earlyCheckinRequest->booking()->with('payment')->firstOrFail();

        if ($booking->status !== BookingStatus::CONFIRMED) {
            throw ValidationException::withMessages([
                'status' => ['Đơn không còn ở trạng thái đã xác nhận, không thể duyệt yêu cầu nhận phòng sớm.'],
            ]);
        }

        return DB::transaction(function () use ($earlyCheckinRequest, $booking, $staff) {
            $fee = (float) $earlyCheckinRequest->fee_amount;

            $this->incidentalInvoiceService->addItem(
                $booking, 'surcharge', "Phụ phí nhận phòng sớm {$earlyCheckinRequest->hours_early} giờ (đã duyệt)", $fee
            );

            $earlyCheckinRequest->update([
                'status'     => 'approved',
                'handled_by' => $staff->id,
                'handled_at' => now(),
            ]);

            $feeText = number_format($fee, 0, ',', '.') . 'đ';
            $arrivalTime = substr($earlyCheckinRequest->requested_arrival_time, 0, 5);
            $booking->user?->notify(new BookingStatusChanged(
                $booking,
                "Yêu cầu nhận phòng sớm lúc {$arrivalTime} cho đơn {$booking->booking_code} đã được duyệt. Phụ phí {$feeText} đã ghi vào hóa đơn phát sinh, thanh toán khi trả phòng."
            ));

            return [
                'request' => $earlyCheckinRequest->fresh(),
                'booking' => $booking->fresh(['payment', 'incidentalInvoice.items']),
            ];
        });
    }

    public function reject(EarlyCheckinRequest $earlyCheckinRequest, User $staff, ?string $note): EarlyCheckinRequest
    {
        if (! $earlyCheckinRequest->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Yêu cầu này đã được xử lý trước đó.'],
            ]);
        }

        $earlyCheckinRequest->update([
            'status'     => 'rejected',
            'staff_note' => $note,
            'handled_by' => $staff->id,
            'handled_at' => now(),
        ]);

        $booking = $earlyCheckinRequest->booking;
        $message = "Yêu cầu nhận phòng sớm cho đơn {$booking->booking_code} đã bị từ chối." . ($note ? " Lý do: {$note}" : '') . ' Bạn có thể nhận phòng vào giờ chuẩn hoặc liên hệ khách sạn nếu cần hỗ trợ thêm.';
        $booking->user?->notify(new BookingStatusChanged($booking, $message));

        return $earlyCheckinRequest->fresh();
    }
}
