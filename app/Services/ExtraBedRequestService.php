<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingStatusLog;
use App\Models\ExtraBedRequest;
use App\Models\RoomType;
use App\Models\User;
use App\Notifications\BookingStatusChanged;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Xử lý (resolve) 1 yêu cầu giường phụ đang "chờ tư vấn" — dùng chung cho cả
 * 2 lối vào: khách tự chọn phương án (Customer\ExtraBedRequestController) và
 * nhân viên/admin xử lý (Staff|Admin\ExtraBedRequestController). Ai gọi tới
 * trước (transaction commit trước) thắng — người gọi sau nhận
 * ValidationException thay vì ghi đè âm thầm, xem resolve().
 */
class ExtraBedRequestService
{
    private const CHOICES = ['upgrade_room', 'add_room', 'drop_extra_bed', 'waitlist', 'staff_cancel'];

    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly PricingService $pricingService,
        private readonly BookingService $bookingService,
    ) {}

    public function adminList(array $filters = []): LengthAwarePaginator
    {
        $query = ExtraBedRequest::with(['booking', 'bookingItem.roomType'])->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(15)->withQueryString();
    }

    public function resolve(ExtraBedRequest $extraBedRequest, string $choice, ?User $staff = null, ?int $newRoomTypeId = null): ExtraBedRequest
    {
        if (! in_array($choice, self::CHOICES, true)) {
            throw ValidationException::withMessages(['choice' => ['Lựa chọn không hợp lệ.']]);
        }

        $resolved = DB::transaction(function () use ($extraBedRequest, $choice, $staff, $newRoomTypeId) {
            $locked = ExtraBedRequest::whereKey($extraBedRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'status' => ['Yêu cầu giường phụ này đã được xử lý trước đó.'],
                ]);
            }

            $booking = Booking::with('bookingItems.roomType')->whereKey($locked->booking_id)->lockForUpdate()->firstOrFail();

            match ($choice) {
                'upgrade_room'   => $this->applyUpgradeRoom($booking, $locked, $newRoomTypeId),
                'add_room'       => $this->applyAddRoom($booking, $locked),
                'drop_extra_bed' => $this->applyDropExtraBed($booking, $locked),
                'waitlist', 'staff_cancel' => null, // xử lý riêng, xem dưới
            };

            $locked->update([
                'status'     => $choice === 'waitlist' ? 'waitlisted' : 'resolved',
                'resolution' => $choice,
                'handled_by' => $staff?->id,
                'handled_at' => now(),
            ]);

            return $locked->fresh(['booking']);
        });

        // staff_cancel gọi BookingService::cancelByAdmin() SAU KHI transaction
        // ở trên đã commit — method đó tự mở transaction riêng và gọi hoàn
        // tiền qua HTTP (VNPay) NGOÀI transaction (xem comment trong chính
        // method đó), không được lồng vào bên trong transaction đang giữ lock
        // ở trên.
        if ($choice === 'staff_cancel') {
            $this->bookingService->cancelByAdmin($resolved->booking);
        }

        return $resolved;
    }

    /**
     * Đổi sang loại phòng lớn hơn — không còn cần giường phụ nữa vì sức
     * chứa phòng mới đã đủ. Tương tự RoomChangeRequestService::approve()
     * nhưng đơn giản hơn: booking ở giai đoạn này luôn UNPAID (chưa từng qua
     * CONFIRMED), không cần xử lý mở lại payment đã PAID.
     */
    private function applyUpgradeRoom(Booking $booking, ExtraBedRequest $extraBedRequest, ?int $newRoomTypeId): void
    {
        if (! $newRoomTypeId) {
            throw ValidationException::withMessages(['room_type_id' => ['Vui lòng chọn loại phòng muốn đổi sang.']]);
        }

        $item = $booking->bookingItems->firstWhere('id', $extraBedRequest->booking_item_id) ?? $booking->bookingItems->first();

        $roomType = RoomType::where('status', 'active')->findOrFail($newRoomTypeId);

        $capacity = $roomType->capacity * $item->quantity;
        if ($item->adults + $item->children > $capacity) {
            throw ValidationException::withMessages([
                'room_type_id' => ["Phòng \"{$roomType->name}\" tối đa {$capacity} khách, không đủ chỗ cho {$item->adults} người lớn + {$item->children} trẻ em của đơn này."],
            ]);
        }

        if (! $this->availabilityService->canBook($roomType->id, $booking->check_in->toDateString(), $booking->check_out->toDateString(), $item->quantity, null, $booking->id)) {
            throw ValidationException::withMessages([
                'room_type_id' => ['Loại phòng này đã hết trong khoảng ngày của đơn — không thể đổi.'],
            ]);
        }

        $pricing = $this->pricingService->calculate($roomType, $booking->check_in->toDateString(), $booking->check_out->toDateString(), $item->quantity, $item->children);

        $item->update([
            'room_type_id'    => $roomType->id,
            'extra_beds'      => 0,
            'price_per_night' => $pricing['unit_price'],
            'nights'          => $pricing['nights'],
            'subtotal'        => $pricing['room_subtotal'],
            'child_surcharge' => $pricing['child_surcharge'],
            'price_breakdown' => $pricing['nightly_breakdown'],
        ]);

        $this->recalculateBookingTotal($booking);
        $this->confirmBooking($booking, "Đơn {$booking->booking_code} đã được xác nhận với loại phòng \"{$roomType->name}\" thay cho yêu cầu giường phụ ban đầu.");
    }

    /**
     * Đặt thêm 1 phòng CÙNG loại để tách khách ra 2 phòng — không ai còn
     * vượt sức chứa nên không cần giường phụ nữa.
     */
    private function applyAddRoom(Booking $booking, ExtraBedRequest $extraBedRequest): void
    {
        $item = $booking->bookingItems->firstWhere('id', $extraBedRequest->booking_item_id) ?? $booking->bookingItems->first();

        // quantity: 1 — item->quantity hiện tại đã được tính vào bookedQuantity
        // (booking đang ở trạng thái holding), chỉ cần hỏi có dư ít nhất 1
        // phòng cùng loại nữa hay không (giống SuggestAlternativesToCustomer).
        if (! $this->availabilityService->canBook($item->room_type_id, $booking->check_in->toDateString(), $booking->check_out->toDateString(), 1)) {
            throw ValidationException::withMessages([
                'choice' => ['Không còn phòng cùng loại để thêm — vui lòng chọn phương án khác.'],
            ]);
        }

        $newQuantity = $item->quantity + 1;
        $pricing = $this->pricingService->calculate($item->roomType, $booking->check_in->toDateString(), $booking->check_out->toDateString(), $newQuantity, $item->children);

        $item->update([
            'quantity'        => $newQuantity,
            'extra_beds'      => 0,
            'price_per_night' => $pricing['unit_price'],
            'nights'          => $pricing['nights'],
            'subtotal'        => $pricing['room_subtotal'],
            'child_surcharge' => $pricing['child_surcharge'],
            'price_breakdown' => $pricing['nightly_breakdown'],
        ]);

        $this->recalculateBookingTotal($booking);
        $this->confirmBooking($booking, "Đơn {$booking->booking_code} đã được xác nhận với 1 phòng \"{$item->roomType->name}\" thêm vào thay cho yêu cầu giường phụ ban đầu.");
    }

    /**
     * Khách/nhân viên quyết định không cần giường phụ nữa — giữ nguyên
     * phòng đã chọn, đơn tiếp tục như bình thường (không cấp giường phụ).
     */
    private function applyDropExtraBed(Booking $booking, ExtraBedRequest $extraBedRequest): void
    {
        if ($extraBedRequest->booking_item_id) {
            BookingItem::whereKey($extraBedRequest->booking_item_id)->update(['extra_beds' => 0]);
        }

        $this->confirmBooking($booking, "Đơn {$booking->booking_code} đã được xác nhận — không cấp giường phụ theo lựa chọn của bạn.");
    }

    /**
     * Chuyển từ pending_consultation sang confirmed + mở lại đồng hồ đặt cọc
     * 30 phút (không có lúc tạo đơn vì khi đó đơn còn đang chờ tư vấn, xem
     * BookingService::create()) — dùng chung cho 3 lựa chọn upgrade_room/
     * add_room/drop_extra_bed, khác waitlist (giữ nguyên pending_consultation)
     * và staff_cancel (không confirm, hủy hẳn — xử lý ở resolve()).
     */
    private function confirmBooking(Booking $booking, string $customerMessage): void
    {
        $oldStatus = $booking->status;

        $booking->update([
            'status'             => BookingStatus::CONFIRMED,
            'deposit_expires_at' => now()->addMinutes(BookingService::DEPOSIT_HOLD_MINUTES),
        ]);

        BookingStatusLog::create([
            'booking_id'  => $booking->id,
            'from_status' => $oldStatus->value,
            'to_status'   => BookingStatus::CONFIRMED->value,
            'note'        => 'Đã resolve yêu cầu giường phụ — đơn được xác nhận, chờ đặt cọc/thanh toán trong ' . BookingService::DEPOSIT_HOLD_MINUTES . ' phút.',
        ]);

        $booking->user?->notify(new BookingStatusChanged($booking, $customerMessage));
    }

    private function recalculateBookingTotal(Booking $booking): void
    {
        $total = $booking->bookingItems()->sum('subtotal')
            + $booking->bookingItems()->sum('child_surcharge')
            + $booking->bookingItems()->sum('extra_bed_surcharge');
        $newTotal = max(0, round($total - (float) $booking->discount_amount, 2));

        $booking->update(['total_amount' => $newTotal]);
        $booking->payment?->update(['amount' => $newTotal]);
    }
}
