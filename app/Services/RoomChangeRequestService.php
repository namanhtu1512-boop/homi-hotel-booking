<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\PaymentStatusLog;
use App\Models\RoomChangeRequest;
use App\Models\RoomType;
use App\Models\User;
use App\Notifications\BookingStatusChanged;
use App\Notifications\NewRoomChangeRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Khách gửi yêu cầu đổi loại phòng/ngày ở cho 1 đơn đã đặt, staff/admin duyệt
 * hoặc từ chối. Chỉ áp dụng khi đơn chưa check-in (PENDING/CONFIRMED) — tránh
 * phải xử lý reassign phòng vật lý đã gán (BookingItemRoom). Đơn đặt đoàn
 * (nhiều loại phòng) chỉ đổi được LOẠI PHÒNG cho 1 dòng cụ thể, không đổi
 * ngày ở (ngày ở áp dụng chung cho cả đơn).
 *
 * Dòng đơn có quantity > 1 (nhiều phòng cùng loại, VD đặt 3 phòng Deluxe
 * chung 1 dòng): khách có thể chỉ đổi loại phòng cho MỘT PHẦN số phòng đó
 * (xem field `quantity` trên RoomChangeRequest) — approve() tách dòng cũ
 * thành 2 BookingItem (phần giữ nguyên loại cũ + phần đã đổi loại mới),
 * khách/trẻ em của phần đổi được chia tỉ lệ theo số phòng.
 *
 * Khi duyệt, hệ thống tự tính lại giá + re-check availability cho tổ hợp
 * loại phòng/ngày mới rồi cập nhật thẳng vào booking (booking_items +
 * total_amount luôn phản ánh ĐÚNG giá trị phòng hiện tại).
 *
 * Nếu đơn ĐÃ thanh toán đủ mà tổng tiền thay đổi (tăng HOẶC giảm): mở lại
 * payment về PENDING — KHÔNG chặn cứng bằng cách xóa dữ liệu, mà tận dụng
 * đúng cơ chế sẵn có của Booking::canCheckIn() (chỉ cho check-in khi PAID)
 * để tự động ẩn nút "Check-in" + hiện cảnh báo "Khách cần đặt cọc hoặc thanh
 * toán trước khi có thể check-in" cho tới khi staff xác nhận đã thu đủ phần
 * chênh lệch (nút "Xác nhận đã thu đủ số tiền còn lại" tự xuất hiện qua
 * Booking::canMarkPaymentAsPaid()) — tránh phải phân bổ số tiền còn thiếu
 * dở dang cho TỪNG phòng vật lý lúc trả phòng (computeRoomSettlementAmounts()
 * chia theo tỷ lệ, sẽ lắt nhắt/khó hiểu nếu để tới lúc đó mới thu). Badge
 * hiển thị "Đã thanh toán 1 phần" (không phải "Đang xử lý" mặc định của
 * PENDING) — xem Payment::displayStatusLabel().
 *
 * Nếu đơn CHƯA thanh toán đủ: chỉ cập nhật thẳng payment.amount theo tổng mới.
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

        if ($booking->roomChangeRequests()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'status' => ['Đơn này đang có 1 yêu cầu đổi phòng chờ duyệt, vui lòng chờ xử lý xong trước khi gửi yêu cầu mới.'],
            ]);
        }

        $isGroupBooking = $booking->bookingItems->count() > 1;

        if ($isGroupBooking) {
            // Đơn đặt đoàn (nhiều loại phòng): khách chỉ được đổi LOẠI PHÒNG
            // cho 1 dòng cụ thể trong đơn, không tự đổi được ngày ở (vì ngày
            // ở áp dụng chung cho toàn bộ đơn, đổi ngày sẽ ảnh hưởng mọi loại
            // phòng khác trong cùng đơn — cần lễ tân xử lý thủ công).
            $bookingItemId = ! empty($data['booking_item_id']) ? (int) $data['booking_item_id'] : null;
            $item          = $bookingItemId ? $booking->bookingItems->firstWhere('id', $bookingItemId) : null;

            if (! $item) {
                throw ValidationException::withMessages([
                    'booking_item_id' => ['Đơn đặt đoàn có nhiều loại phòng, vui lòng chọn loại phòng cụ thể muốn đổi.'],
                ]);
            }

            if (! empty($data['requested_check_in']) || ! empty($data['requested_check_out'])) {
                throw ValidationException::withMessages([
                    'status' => ['Đơn đặt đoàn có nhiều loại phòng, vui lòng liên hệ khách sạn để được hỗ trợ đổi ngày ở.'],
                ]);
            }
        } else {
            $item = $booking->bookingItems->first();
        }

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

        // Dòng đơn có nhiều hơn 1 phòng cùng loại (VD đặt 3 phòng Deluxe
        // chung 1 dòng) — khách có thể chỉ muốn đổi loại phòng cho MỘT PHẦN
        // trong số đó, không đổi cả cụm. Mặc định (không truyền quantity)
        // vẫn là đổi toàn bộ — giữ tương thích hành vi cũ.
        $requestedQuantity = ! empty($data['quantity']) ? (int) $data['quantity'] : $item->quantity;

        if ($requestedQuantity < 1 || $requestedQuantity > $item->quantity) {
            throw ValidationException::withMessages([
                'quantity' => ["Số phòng muốn đổi phải từ 1 đến {$item->quantity}."],
            ]);
        }

        if ($requestedQuantity < $item->quantity) {
            if (! $roomTypeChanged) {
                throw ValidationException::withMessages([
                    'quantity' => ['Chỉ áp dụng đổi 1 phần số phòng khi đổi LOẠI PHÒNG — vui lòng chọn loại phòng mới.'],
                ]);
            }

            if ($datesChanged) {
                throw ValidationException::withMessages([
                    'quantity' => ['Đổi ngày ở chỉ áp dụng khi đổi toàn bộ số phòng của dòng đơn này — vui lòng liên hệ khách sạn nếu cần đổi ngày cho 1 phần.'],
                ]);
            }
        }

        $request = RoomChangeRequest::create([
            'booking_id'              => $booking->id,
            'booking_item_id'         => $isGroupBooking ? $item->id : null,
            'quantity'                => $requestedQuantity,
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

        User::whereIn('role', ['admin', 'staff'])->each(
            fn (User $u) => $u->notify(new NewRoomChangeRequest($request))
        );

        return $request;
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

        $item = $roomChangeRequest->booking_item_id
            ? $booking->bookingItems->firstWhere('id', $roomChangeRequest->booking_item_id)
            : $booking->bookingItems->first();

        if (! $item) {
            throw ValidationException::withMessages([
                'status' => ['Loại phòng được yêu cầu đổi không còn tồn tại trong đơn này — vui lòng xử lý thủ công.'],
            ]);
        }

        $targetRoomTypeId = $roomChangeRequest->requested_room_type_id ?? $item->room_type_id;
        $targetCheckIn     = ($roomChangeRequest->requested_check_in ?? $booking->check_in)->toDateString();
        $targetCheckOut    = ($roomChangeRequest->requested_check_out ?? $booking->check_out)->toDateString();

        $roomType = RoomType::where('status', 'active')->findOrFail($targetRoomTypeId);

        // Số phòng thực sự đổi loại — có thể nhỏ hơn item->quantity nếu
        // khách chỉ muốn đổi 1 PHẦN trong số nhiều phòng cùng loại của dòng
        // đơn (xem create()). Khách/trẻ em của phần đổi được chia tỉ lệ
        // theo số phòng, phần còn lại giữ nguyên loại phòng cũ.
        $changeQuantity = $roomChangeRequest->quantity ?? $item->quantity;
        $isPartial      = $changeQuantity < $item->quantity;

        $splitAdults   = $isPartial ? (int) round($item->adults * $changeQuantity / $item->quantity) : $item->adults;
        $splitChildren = $isPartial ? (int) round($item->children * $changeQuantity / $item->quantity) : $item->children;
        $splitInfants  = $isPartial ? (int) round($item->infants * $changeQuantity / $item->quantity) : $item->infants;

        $capacity = $roomType->capacity * $changeQuantity;
        if ($splitAdults + $splitChildren > $capacity) {
            throw ValidationException::withMessages([
                'status' => ["Phòng \"{$roomType->name}\" tối đa {$capacity} khách cho {$changeQuantity} phòng, không đủ chỗ cho {$splitAdults} người lớn + {$splitChildren} trẻ em."],
            ]);
        }

        $availability = $this->availabilityService->check(
            $targetRoomTypeId, $targetCheckIn, $targetCheckOut, $changeQuantity, null, $booking->id
        );

        if (! $availability['can_book']) {
            throw ValidationException::withMessages([
                'status' => ['Không đủ phòng trống cho loại phòng/ngày yêu cầu — không thể duyệt.'],
            ]);
        }

        $pricing = $this->pricingService->calculate($roomType, $targetCheckIn, $targetCheckOut, $changeQuantity, $splitChildren);

        $remainingPricing = null;
        if ($isPartial) {
            $remainingQuantity = $item->quantity - $changeQuantity;
            $remainingChildren = $item->children - $splitChildren;
            $remainingPricing  = $this->pricingService->calculate($item->roomType, $targetCheckIn, $targetCheckOut, $remainingQuantity, $remainingChildren);
        }

        return DB::transaction(function () use (
            $roomChangeRequest, $booking, $item, $roomType, $targetCheckIn, $targetCheckOut, $pricing, $staff,
            $isPartial, $changeQuantity, $splitAdults, $splitChildren, $splitInfants, $remainingPricing
        ) {
            $oldTotal = (float) $booking->total_amount;

            if ($isPartial) {
                $remainingQuantity = $item->quantity - $changeQuantity;

                // Chỉ cộng/trừ đúng phần CHÊNH LỆCH của dòng phòng đang đổi
                // (cả 2 nửa sau khi tách) vào tổng tiền đơn, không ghi đè
                // thẳng total_amount — đơn có thể còn dòng phòng khác.
                $itemDelta = round(
                    ($pricing['total_price'] + $remainingPricing['total_price']) - ((float) $item->subtotal + (float) $item->child_surcharge),
                    2
                );
                $newTotal = max(0, round($oldTotal + $itemDelta, 2));

                // Dòng cũ giữ loại phòng cũ, chỉ giảm số lượng + khách theo
                // phần KHÔNG đổi.
                $item->update([
                    'quantity'        => $remainingQuantity,
                    'adults'          => $item->adults - $splitAdults,
                    'children'        => $item->children - $splitChildren,
                    'infants'         => $item->infants - $splitInfants,
                    'price_per_night' => $remainingPricing['unit_price'],
                    'nights'          => $remainingPricing['nights'],
                    'subtotal'        => $remainingPricing['room_subtotal'],
                    'child_surcharge' => $remainingPricing['child_surcharge'],
                    'price_breakdown' => $remainingPricing['nightly_breakdown'],
                ]);

                // Dòng mới cho phần đã đổi sang loại phòng khác.
                BookingItem::create([
                    'booking_id'      => $booking->id,
                    'room_type_id'    => $roomType->id,
                    'quantity'        => $changeQuantity,
                    'adults'          => $splitAdults,
                    'children'        => $splitChildren,
                    'infants'         => $splitInfants,
                    'price_per_night' => $pricing['unit_price'],
                    'nights'          => $pricing['nights'],
                    'subtotal'        => $pricing['room_subtotal'],
                    'child_surcharge' => $pricing['child_surcharge'],
                    'price_breakdown' => $pricing['nightly_breakdown'],
                ]);
            } else {
                $itemDelta = round($pricing['total_price'] - ((float) $item->subtotal + (float) $item->child_surcharge), 2);
                $newTotal  = max(0, round($oldTotal + $itemDelta, 2));

                $item->update([
                    'room_type_id'    => $roomType->id,
                    'price_per_night' => $pricing['unit_price'],
                    'nights'          => $pricing['nights'],
                    'subtotal'        => $pricing['room_subtotal'],
                    'child_surcharge' => $pricing['child_surcharge'],
                    'price_breakdown' => $pricing['nightly_breakdown'],
                ]);
            }

            $booking->update([
                'check_in'     => $targetCheckIn,
                'check_out'    => $targetCheckOut,
                'nights'       => $pricing['nights'],
                'total_amount' => $newTotal,
            ]);

            $delta = round($newTotal - $oldTotal, 2);

            // Mô tả CHÍNH XÁC thứ đã đổi (loại phòng và/hoặc ngày ở) để ghi
            // vào cả lịch sử thanh toán lẫn thông báo cho khách — tránh câu
            // chung chung kiểu "làm thay đổi tổng tiền" khiến khách không
            // biết vì sao số tiền phải trả lại tăng/giảm.
            $changeParts = [];
            if ($roomChangeRequest->requested_room_type_id) {
                $fromRoomName = $roomChangeRequest->currentRoomType?->name ?? 'phòng cũ';
                $changeParts[] = "đổi {$changeQuantity} phòng {$fromRoomName} sang {$roomType->name}";
            }
            if ($roomChangeRequest->requested_check_in) {
                $changeParts[] = 'đổi ngày ở sang ' . Carbon::parse($targetCheckIn)->format('d/m/Y') . ' - ' . Carbon::parse($targetCheckOut)->format('d/m/Y');
            }
            $changeDesc = $changeParts ? implode(', ', $changeParts) : 'cập nhật đơn';

            if ($booking->payment) {
                $wasPaid = $booking->payment->status === PaymentStatus::PAID;

                if ($wasPaid && $delta !== 0.0) {
                    // Đã thu đủ THEO GIÁ CŨ — tổng tiền đổi khác giá mới nên
                    // mở lại PENDING để chặn check-in (Booking::canCheckIn()
                    // chỉ chấp nhận PAID) cho tới khi staff xác nhận thu đủ
                    // phần chênh lệch (nút "Xác nhận đã thu đủ số tiền còn
                    // lại" xuất hiện tự động qua canMarkPaymentAsPaid() —
                    // xem Payment::displayStatusLabel() để hiển thị badge
                    // "Đã thanh toán 1 phần" thay vì "Đang xử lý" gây hiểu
                    // lầm là đơn CHƯA thu đồng nào).
                    $booking->payment->update(['amount' => $newTotal, 'status' => PaymentStatus::PENDING]);
                    $deltaNoteText = number_format(abs($delta), 0, ',', '.') . 'đ';
                    PaymentStatusLog::create([
                        'payment_id'  => $booking->payment->id,
                        'changed_by'  => $staff->id,
                        'from_status' => PaymentStatus::PAID->value,
                        'to_status'   => PaymentStatus::PENDING->value,
                        'note'        => $delta > 0
                            ? "Duyệt yêu cầu đổi phòng #{$roomChangeRequest->id} ({$changeDesc}): phát sinh thêm {$deltaNoteText}, mở lại chờ thu phần chênh lệch trước khi check-in."
                            : "Duyệt yêu cầu đổi phòng #{$roomChangeRequest->id} ({$changeDesc}): giảm {$deltaNoteText}, mở lại chờ hoàn phần chênh lệch cho khách.",
                    ]);
                } elseif (! $wasPaid) {
                    // Chưa thanh toán đủ — cập nhật số tiền cần thu theo tổng mới.
                    $booking->payment->update(['amount' => $newTotal]);
                }
            }

            $roomChangeRequest->update([
                'status'      => 'approved',
                'price_delta' => $delta,
                'handled_by'  => $staff->id,
                'handled_at'  => now(),
            ]);

            $deltaText = number_format(abs($delta), 0, ',', '.') . 'đ';
            $message = match (true) {
                $delta > 0 => "Yêu cầu đổi phòng cho đơn {$booking->booking_code} đã được duyệt ({$changeDesc}). Phát sinh thêm {$deltaText} do đổi phòng, vui lòng thanh toán phần chênh lệch này để được nhận/tiếp tục nhận phòng.",
                $delta < 0 => "Yêu cầu đổi phòng cho đơn {$booking->booking_code} đã được duyệt ({$changeDesc}). Tổng tiền giảm {$deltaText} do đổi phòng, khách sạn sẽ liên hệ hoàn lại nếu bạn đã thanh toán trước đó.",
                default    => "Yêu cầu đổi phòng cho đơn {$booking->booking_code} đã được duyệt ({$changeDesc}).",
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
