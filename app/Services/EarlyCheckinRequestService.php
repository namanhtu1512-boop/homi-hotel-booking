<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\EarlyCheckinRequest;
use App\Models\HotelInfo;
use App\Models\PaymentStatusLog;
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
 * Phí cố định 100.000đ/giờ sớm (làm tròn lên), cộng dồn vào
 * payment.surcharge_amount giống các phụ phí phát sinh khác — không thu ngay
 * khi duyệt, chỉ bắt buộc thanh toán khi trả phòng (Booking::canCheckOut()
 * đã đòi hỏi payment.status === PAID sẵn).
 */
class EarlyCheckinRequestService
{
    public const FEE_PER_HOUR = 100000;

    public const MAX_HOURS_EARLY = 3;

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

        $minutesEarly = $requested->diffInMinutes($standard);
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

            $booking->increment('total_amount', $fee);

            if ($booking->payment) {
                $feeText = number_format($fee, 0, ',', '.') . 'đ';
                $note = "Phụ phí nhận phòng sớm {$earlyCheckinRequest->hours_early} giờ (+{$feeText})";

                $booking->payment->increment('surcharge_amount', $fee);
                $booking->payment->update([
                    'surcharge_note' => $booking->payment->surcharge_note
                        ? $booking->payment->surcharge_note . " | {$note}"
                        : $note,
                ]);

                // KHÔNG mở lại PENDING dù đơn đã PAID trước đó — khác với
                // applyExtraCharge()/RoomChangeRequestService::approve() (chỉ
                // chạy sau khi khách đã CHECKED_IN nên mở PENDING chỉ chặn
                // trả phòng). Ở đây duyệt xảy ra TRƯỚC khi nhận phòng — nếu mở
                // PENDING sẽ vô tình chặn luôn canCheckIn() (đòi hỏi
                // PAID/DEPOSIT_PAID), trái với yêu cầu "không chặn nhận phòng,
                // chỉ bắt buộc thanh toán khi trả phòng". Đánh đổi: hệ thống
                // không tự cưỡng chế thu khoản phụ phí này ở bước trả phòng
                // nữa (canCheckOut() chỉ check status===PAID, không so số
                // tiền) — lễ tân cần tự thu dựa vào surcharge_note hiển thị.
                $status = $booking->payment->status;
                $booking->payment->increment('amount', $fee);

                PaymentStatusLog::create([
                    'payment_id'  => $booking->payment->id,
                    'changed_by'  => $staff->id,
                    'from_status' => $status->value,
                    'to_status'   => $status->value,
                    'note'        => "Duyệt yêu cầu nhận phòng sớm #{$earlyCheckinRequest->id}: {$note}.",
                ]);
            }

            $earlyCheckinRequest->update([
                'status'     => 'approved',
                'handled_by' => $staff->id,
                'handled_at' => now(),
            ]);

            $feeText = number_format($fee, 0, ',', '.') . 'đ';
            $arrivalTime = substr($earlyCheckinRequest->requested_arrival_time, 0, 5);
            $booking->user?->notify(new BookingStatusChanged(
                $booking,
                "Yêu cầu nhận phòng sớm lúc {$arrivalTime} cho đơn {$booking->booking_code} đã được duyệt. Phụ phí {$feeText} đã cộng vào tổng tiền, thanh toán khi trả phòng."
            ));

            return [
                'request' => $earlyCheckinRequest->fresh(),
                'booking' => $booking->fresh('payment'),
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
