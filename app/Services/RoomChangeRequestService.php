<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\PaymentStatusLog;
use App\Models\RoomChangeRequest;
use App\Models\RoomType;
use App\Models\User;
use App\Notifications\BookingStatusChanged;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Khách gửi yêu cầu đổi loại phòng/ngày ở cho 1 đơn đã đặt, staff/admin duyệt
 * hoặc từ chối. Chỉ áp dụng cho đơn 1 loại phòng duy nhất, chưa check-in
 * (PENDING/CONFIRMED) — tránh phải xử lý reassign phòng vật lý đã gán
 * (BookingItemRoom) hoặc chia nhỏ nhiều loại phòng trong 1 đơn.
 *
 * Khi duyệt, hệ thống tự tính lại giá + re-check availability cho tổ hợp
 * loại phòng/ngày mới rồi cập nhật thẳng vào booking; nhưng việc thu thêm
 * hay hoàn lại phần tiền chênh lệch do STAFF xử lý thủ công ở ngoài luồng
 * (giống cơ chế addSurcharge() của BookingService) — hệ thống chỉ mở lại
 * payment về PENDING nếu đơn đã PAID trước đó để staff biết cần đối soát lại,
 * không tự động trừ/hoàn qua cổng thanh toán.
 */
class RoomChangeRequestService
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly PricingService $pricingService,
    ) {}

    public function create(Booking $booking, User $customer, array $data): RoomChangeRequest
    {
        if ((int) $booking->user_id !== $customer->id) {
            abort(403);
        }

        if (! in_array($booking->status, [BookingStatus::PENDING, BookingStatus::CONFIRMED], true)) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể yêu cầu đổi phòng khi đơn đang chờ xác nhận hoặc đã xác nhận (chưa nhận phòng).'],
            ]);
        }

        if ($booking->bookingItems->count() !== 1) {
            throw ValidationException::withMessages([
                'status' => ['Đơn có nhiều loại phòng, vui lòng liên hệ khách sạn để được hỗ trợ đổi phòng.'],
            ]);
        }

        if ($booking->roomChangeRequests()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'status' => ['Đơn này đang có 1 yêu cầu đổi phòng chờ duyệt, vui lòng chờ xử lý xong trước khi gửi yêu cầu mới.'],
            ]);
        }

        $item = $booking->bookingItems->first();

        $requestedRoomTypeId = ! empty($data['requested_room_type_id']) ? (int) $data['requested_room_type_id'] : null;
        $requestedCheckIn    = $data['requested_check_in'] ?? null;
        $requestedCheckOut   = $data['requested_check_out'] ?? null;

        $roomTypeChanged = $requestedRoomTypeId && $requestedRoomTypeId !== $item->room_type_id;
        $datesChanged    = $requestedCheckIn && $requestedCheckOut
            && (! $booking->check_in->isSameDay($requestedCheckIn) || ! $booking->check_out->isSameDay($requestedCheckOut));

        if (! $roomTypeChanged && ! $datesChanged) {
            throw ValidationException::withMessages([
                'status' => ['Vui lòng chọn loại phòng mới hoặc ngày ở mới khác với hiện tại.'],
            ]);
        }

        return RoomChangeRequest::create([
            'booking_id'              => $booking->id,
            'user_id'                 => $customer->id,
            'current_room_type_id'    => $item->room_type_id,
            'requested_room_type_id'  => $roomTypeChanged ? $requestedRoomTypeId : null,
            'current_check_in'        => $booking->check_in,
            'current_check_out'       => $booking->check_out,
            'requested_check_in'      => $datesChanged ? $requestedCheckIn : null,
            'requested_check_out'     => $datesChanged ? $requestedCheckOut : null,
            'reason'                  => $data['reason'] ?? null,
            'status'                  => 'pending',
        ]);
    }

    public function adminList(array $filters = []): LengthAwarePaginator
    {
        $query = RoomChangeRequest::with(['booking', 'user', 'currentRoomType', 'requestedRoomType'])->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * @return array{request: RoomChangeRequest, booking: Booking, delta: float}
     */
    public function approve(RoomChangeRequest $roomChangeRequest, User $staff): array
    {
        if (! $roomChangeRequest->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Yêu cầu này đã được xử lý trước đó.'],
            ]);
        }

        $booking = $roomChangeRequest->booking()->with(['bookingItems', 'payment'])->firstOrFail();

        if ($booking->bookingItems->count() !== 1) {
            throw ValidationException::withMessages([
                'status' => ['Đơn có nhiều loại phòng, không thể tự động đổi — vui lòng xử lý thủ công.'],
            ]);
        }

        $item = $booking->bookingItems->first();

        $targetRoomTypeId = $roomChangeRequest->requested_room_type_id ?? $item->room_type_id;
        $targetCheckIn     = ($roomChangeRequest->requested_check_in ?? $booking->check_in)->toDateString();
        $targetCheckOut    = ($roomChangeRequest->requested_check_out ?? $booking->check_out)->toDateString();

        $roomType = RoomType::where('status', 'active')->findOrFail($targetRoomTypeId);

        $capacity = $roomType->capacity * $item->quantity;
        if ($item->adults + $item->children > $capacity) {
            throw ValidationException::withMessages([
                'status' => ["Phòng \"{$roomType->name}\" tối đa {$capacity} khách, không đủ chỗ cho {$item->adults} người lớn + {$item->children} trẻ em của đơn này."],
            ]);
        }

        $availability = $this->availabilityService->check(
            $targetRoomTypeId, $targetCheckIn, $targetCheckOut, $item->quantity, null, $booking->id
        );

        if (! $availability['can_book']) {
            throw ValidationException::withMessages([
                'status' => ['Không đủ phòng trống cho loại phòng/ngày yêu cầu — không thể duyệt.'],
            ]);
        }

        $pricing = $this->pricingService->calculate($roomType, $targetCheckIn, $targetCheckOut, $item->quantity, $item->children);

        return DB::transaction(function () use ($roomChangeRequest, $booking, $item, $roomType, $targetCheckIn, $targetCheckOut, $pricing, $staff) {
            $oldTotal = (float) $booking->total_amount;
            $newTotal = max(0, round($pricing['total_price'] - (float) $booking->discount_amount, 2));

            $item->update([
                'room_type_id'    => $roomType->id,
                'price_per_night' => $pricing['unit_price'],
                'nights'          => $pricing['nights'],
                'subtotal'        => $pricing['room_subtotal'],
                'child_surcharge' => $pricing['child_surcharge'],
                'price_breakdown' => $pricing['nightly_breakdown'],
            ]);

            $booking->update([
                'check_in'     => $targetCheckIn,
                'check_out'    => $targetCheckOut,
                'nights'       => $pricing['nights'],
                'total_amount' => $newTotal,
            ]);

            $delta = round($newTotal - $oldTotal, 2);

            if ($booking->payment) {
                $wasPaid = $booking->payment->status === PaymentStatus::PAID;
                $booking->payment->update(['amount' => $newTotal]);

                if ($wasPaid && $delta !== 0.0) {
                    $booking->payment->update(['status' => PaymentStatus::PENDING]);
                    PaymentStatusLog::create([
                        'payment_id'  => $booking->payment->id,
                        'changed_by'  => $staff->id,
                        'from_status' => PaymentStatus::PAID->value,
                        'to_status'   => PaymentStatus::PENDING->value,
                        'note'        => "Duyệt yêu cầu đổi phòng #{$roomChangeRequest->id} làm thay đổi tổng tiền, mở lại chờ thu/hoàn phần chênh lệch.",
                    ]);
                }
            }

            $roomChangeRequest->update([
                'status'     => 'approved',
                'handled_by' => $staff->id,
                'handled_at' => now(),
            ]);

            $deltaText = number_format(abs($delta), 0, ',', '.') . 'đ';
            $message = match (true) {
                $delta > 0 => "Yêu cầu đổi phòng cho đơn {$booking->booking_code} đã được duyệt. Tổng tiền tăng thêm {$deltaText}, vui lòng thanh toán phần chênh lệch.",
                $delta < 0 => "Yêu cầu đổi phòng cho đơn {$booking->booking_code} đã được duyệt. Tổng tiền giảm {$deltaText}, khách sạn sẽ liên hệ hoàn lại nếu bạn đã thanh toán trước đó.",
                default    => "Yêu cầu đổi phòng cho đơn {$booking->booking_code} đã được duyệt.",
            };
            $booking->user?->notify(new BookingStatusChanged($booking, $message));

            return [
                'request' => $roomChangeRequest->fresh(),
                'booking' => $booking->fresh(['bookingItems.roomType', 'payment']),
                'delta'   => $delta,
            ];
        });
    }

    public function reject(RoomChangeRequest $roomChangeRequest, User $staff, ?string $note): RoomChangeRequest
    {
        if (! $roomChangeRequest->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Yêu cầu này đã được xử lý trước đó.'],
            ]);
        }

        $roomChangeRequest->update([
            'status'     => 'rejected',
            'staff_note' => $note,
            'handled_by' => $staff->id,
            'handled_at' => now(),
        ]);

        $booking = $roomChangeRequest->booking;
        $message = "Yêu cầu đổi phòng cho đơn {$booking->booking_code} đã bị từ chối." . ($note ? " Lý do: {$note}" : '');
        $booking->user?->notify(new BookingStatusChanged($booking, $message));

        return $roomChangeRequest->fresh();
    }
}
