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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Khách gửi yêu cầu nhận phòng sớm hơn giờ chuẩn của khách sạn. Đây là luồng
 * RIÊNG, độc lập với phụ phí nhận phòng sớm tự động (% giá phòng, không cần
 * duyệt) đã có sẵn ở BookingService::checkIn()/applyEarlyCheckinSurchargeIfNeeded()
 * — không đụng vào cơ chế đó.
 *
 * Sớm tối đa self::AUTO_APPROVE_MAX_HOURS (1 giờ): tự động DUYỆT ngay lúc
 * gửi, không cần chờ staff — khách nhận thông báo được vào phòng sớm ngay
 * lập tức. Sớm hơn thế: vẫn phải chờ staff/admin duyệt dựa trên tình trạng
 * phòng trống thực tế ngày hôm đó (không giới hạn số giờ cứng — staff toàn
 * quyền quyết định theo phòng còn trống hay không).
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

    /**
     * Sớm tối đa bằng số giờ này được tự động duyệt ngay khi gửi yêu cầu,
     * không cần staff can thiệp — sớm hơn phải chờ staff duyệt dựa trên
     * tình trạng phòng trống thực tế.
     */
    public const AUTO_APPROVE_MAX_HOURS = 1;

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

        if ($booking->earlyCheckinRequests()->exists()) {
            throw ValidationException::withMessages([
                'status' => ['Đơn này đã gửi yêu cầu nhận phòng sớm trước đó, mỗi đơn chỉ được gửi 1 lần.'],
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

        $autoApprove = $hoursEarly <= self::AUTO_APPROVE_MAX_HOURS;

        $attributes = [
            'booking_id'              => $booking->id,
            'user_id'                 => $customer->id,
            'requested_arrival_time'  => $requestedTime,
            'hours_early'             => $hoursEarly,
            'fee_amount'              => $hoursEarly * self::FEE_PER_HOUR,
            'reason'                  => $data['reason'] ?? null,
            'status'                  => $autoApprove ? 'approved' : 'pending',
            'handled_at'              => $autoApprove ? now() : null,
        ];

        if (! $autoApprove) {
            $request = EarlyCheckinRequest::create($attributes);

            User::whereIn('role', ['admin', 'staff'])->each(
                fn (User $u) => $u->notify(new NewEarlyCheckinRequest($request))
            );

            return $request;
        }

        return DB::transaction(function () use ($attributes, $booking) {
            $request = EarlyCheckinRequest::create($attributes);

            $this->grantEarlyCheckin($request, $booking, ' Yêu cầu nhận phòng sớm trong vòng ' . self::AUTO_APPROVE_MAX_HOURS . ' giờ được tự động duyệt.');

            return $request;
        });
    }

    /**
     * Cộng phụ phí nhận phòng sớm vào hóa đơn phát sinh + báo khách — dùng
     * chung cho cả nhánh tự động duyệt (create(), sớm ≤ AUTO_APPROVE_MAX_HOURS)
     * và nhánh staff duyệt tay (approve()).
     */
    private function grantEarlyCheckin(EarlyCheckinRequest $request, Booking $booking, string $extraNote = ''): void
    {
        $fee = (float) $request->fee_amount;

        $this->incidentalInvoiceService->addItem(
            $booking, 'surcharge', "Phụ phí nhận phòng sớm {$request->hours_early} giờ (đã duyệt)", $fee
        );

        $feeText = number_format($fee, 0, ',', '.') . 'đ';
        $arrivalTime = substr($request->requested_arrival_time, 0, 5);
        $booking->user?->notify(new BookingStatusChanged(
            $booking,
            "Yêu cầu nhận phòng sớm lúc {$arrivalTime} cho đơn {$booking->booking_code} đã được duyệt. Phụ phí {$feeText} đã ghi vào hóa đơn phát sinh, thanh toán khi trả phòng.{$extraNote}"
        ));
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
     * Danh sách phòng đang nhận phòng sớm trong 1 ngày cụ thể — chỉ tính các
     * yêu cầu ĐÃ DUYỆT có booking.check_in đúng ngày đó (nhận phòng sớm chỉ
     * áp dụng cho đúng ngày nhận phòng), loại đơn đã hủy. Trả về 1 dòng cho
     * mỗi booking_item của đơn vì yêu cầu là theo cả đơn, không tách theo
     * từng phòng.
     */
    public function usageOnDate(string $date): Collection
    {
        return EarlyCheckinRequest::with(['booking.bookingItems.roomType', 'booking.bookingItems.rooms'])
            ->where('status', 'approved')
            ->whereHas('booking', fn ($q) => $q
                ->whereDate('check_in', $date)
                ->where('status', '!=', BookingStatus::CANCELLED->value)
            )
            ->get()
            ->flatMap(fn (EarlyCheckinRequest $request) => $request->booking->bookingItems->map(fn ($item) => [
                'request'     => $request,
                'booking'     => $request->booking,
                'bookingItem' => $item,
            ]))
            ->values();
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
            $earlyCheckinRequest->update([
                'status'     => 'approved',
                'handled_by' => $staff->id,
                'handled_at' => now(),
            ]);

            $this->grantEarlyCheckin($earlyCheckinRequest, $booking);

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
