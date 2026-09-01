<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingItemRoom;
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
 * calculateFee(). Mỗi phòng khách chọn được ghi 1 dòng hóa đơn phát sinh
 * RIÊNG, gắn đúng booking_item_room_id — bắt buộc phải tách vì
 * BookingService::computeRoomSettlementAmounts() chỉ cộng phụ phí vào tiền
 * phải thu của 1 phòng cụ thể nếu phụ phí đó có gắn đúng phòng ấy (đơn nhiều
 * phòng); nếu để 1 dòng gộp không gắn phòng, phụ phí sẽ "biến mất" khỏi số
 * tiền cần thu khi từng phòng trả riêng.
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

        $roomSelections = $this->normalizeRoomSelections($booking, $data['room_selections'] ?? null);
        $this->assertRoomsNotAlreadyRequested($booking, $roomSelections);

        $isAfterEighteen = $requestedTime >= '18:00';
        $fee = self::calculateFee($hoursLate, $isAfterEighteen, self::lastNightTotal($booking, $roomSelections));

        $autoApprove = $hoursLate <= self::AUTO_APPROVE_MAX_HOURS && ! $isAfterEighteen;

        $attributes = [
            'booking_id'               => $booking->id,
            'user_id'                  => $customer->id,
            'requested_checkout_time'  => $requestedTime,
            'hours_late'               => $hoursLate,
            'fee_amount'               => $fee,
            'reason'                   => $data['reason'] ?? null,
            'room_selections'          => $roomSelections,
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
     * Chuẩn hóa + validate `room_selections` khách gửi lên: mảng
     * booking_item_room_id (khác EarlyCheckinRequestService — ở đó chưa
     * check-in nên chọn theo SỐ LƯỢNG trừu tượng của dòng, còn ở đây đơn ĐÃ
     * check-in nên chọn đúng theo PHÒNG VẬT LÝ đang ở). Chỉ chấp nhận phòng
     * đang thực sự lưu trú (Booking::inHouseBookingItemRooms()) — chặn chọn
     * phòng chưa check-in hoặc đã check-out, hoặc không thuộc đơn.
     *
     * @param  array<int, int|string>|null  $raw
     * @return array<int, int>|null null = khách không chọn gì cụ thể, áp dụng TOÀN BỘ phòng đang ở
     */
    private function normalizeRoomSelections(Booking $booking, ?array $raw): ?array
    {
        if (empty($raw)) {
            return null;
        }

        $eligibleIds = $booking->inHouseBookingItemRooms()->pluck('id');
        $selected = collect($raw)->map(fn ($id) => (int) $id)->unique()->values();

        if ($selected->diff($eligibleIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'room_selections' => ['Danh sách phòng chọn không hợp lệ — chỉ chọn được phòng đang thực sự lưu trú.'],
            ]);
        }

        if ($selected->isEmpty()) {
            throw ValidationException::withMessages([
                'room_selections' => ['Vui lòng chọn ít nhất 1 phòng.'],
            ]);
        }

        return $selected->all();
    }

    /**
     * Chặn gửi yêu cầu mới cho phòng ĐÃ có yêu cầu trả phòng muộn còn hiệu
     * lực (đang chờ duyệt hoặc đã duyệt) — tránh khách bấm gửi nhiều lần
     * (VD double-click, hoặc gửi lại nhầm) khiến 1 phòng bị tính phí trả
     * phòng muộn nhiều lần. Phòng đã bị TỪ CHỐI trước đó vẫn gửi lại được
     * bình thường. Thay thế guard cũ (chặn theo CẢ ĐƠN chỉ vì có 1 yêu cầu
     * pending) — giờ chặn đúng theo PHÒNG, phòng khác chưa từng yêu cầu vẫn
     * gửi được dù đơn đang có yêu cầu khác cho phòng còn lại.
     */
    private function assertRoomsNotAlreadyRequested(Booking $booking, ?array $roomSelections): void
    {
        $alreadyRequestedIds = $booking->lateCheckoutRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->get()
            ->flatMap(fn (LateCheckoutRequest $existing) => $existing->setRelation('booking', $booking)
                ->selectedBookingItemRooms()
                ->pluck('id'));

        if ($alreadyRequestedIds->isEmpty()) {
            return;
        }

        $targetIds = $roomSelections ?? $booking->inHouseBookingItemRooms()->pluck('id')->all();

        if (collect($targetIds)->intersect($alreadyRequestedIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'room_selections' => ['1 hoặc nhiều phòng đã chọn đã có yêu cầu trả phòng muộn trước đó (đang chờ duyệt hoặc đã duyệt).'],
            ]);
        }
    }

    /**
     * Cộng phụ phí trả phòng muộn vào hóa đơn phát sinh + báo khách — dùng
     * chung cho cả nhánh tự động duyệt (create(), trễ ≤ AUTO_APPROVE_MAX_HOURS)
     * và nhánh staff duyệt tay (approve()).
     *
     * Ghi 1 dòng hóa đơn RIÊNG cho MỖI phòng đã chọn (gắn đúng
     * booking_item_room_id, ghi rõ số phòng trong mô tả) thay vì 1 dòng gộp
     * — xem giải thích ở docblock class. Mỗi phòng tự tính phí theo đúng giá
     * dòng đơn của nó (các phòng khác loại có thể khác giá) — tổng các dòng
     * này có thể lệch $request->fee_amount vài đồng do làm tròn riêng từng
     * dòng, không đáng kể.
     */
    private function grantLateCheckout(LateCheckoutRequest $request, Booking $booking, string $extraNote = ''): void
    {
        $newTime = substr($request->requested_checkout_time, 0, 5);
        $isAfterEighteen = $newTime >= '18:00';
        // Luôn là số nguyên (1-6) vì form chỉ cho chọn theo giờ tròn — xem
        // create(). Ghi rõ số giờ trễ trong mô tả để khách/staff/admin nhìn
        // hóa đơn phát sinh là hiểu ngay, không phải tự trừ giờ chuẩn.
        $hoursLate = (int) $request->hours_late;

        $request->setRelation('booking', $booking);
        $selectedRooms = $request->selectedBookingItemRooms();
        $nightlyRateByItemId = self::nightlyRateByItemId($booking);

        $roomLabels = [];

        foreach ($selectedRooms as $bir) {
            $roomFee = self::calculateFee($hoursLate, $isAfterEighteen, $nightlyRateByItemId[$bir->booking_item_id] ?? 0.0);
            $roomLabels[] = "Phòng {$bir->room->room_number}";

            $this->incidentalInvoiceService->addItem(
                $booking, 'surcharge',
                "Phụ phí trả phòng muộn {$hoursLate} giờ - Phòng {$bir->room->room_number} (tới {$newTime}, đã duyệt)",
                $roomFee, null, null, 1, $bir->id
            );
        }

        $feeText = number_format((float) $request->fee_amount, 0, ',', '.') . 'đ';
        $roomsText = implode(', ', $roomLabels);
        $booking->user?->notify(new BookingStatusChanged(
            $booking,
            "Yêu cầu trả phòng muộn {$hoursLate} giờ (tới {$newTime}, {$roomsText}) cho đơn {$booking->booking_code} đã được duyệt. Phụ phí {$feeText} đã ghi vào hóa đơn phát sinh, thanh toán khi trả phòng.{$extraNote}"
        ));
    }

    /**
     * Phí = % giá phòng đêm cuối, 10%/giờ trễ tính tròn theo từng mốc giờ đã
     * chọn (form chỉ cho chọn nguyên giờ 1-6, không cho nhập lẻ):
     *   - Muộn 1 giờ: 10%    - Muộn 4 giờ: 40%
     *   - Muộn 2 giờ: 20%    - Muộn 5 giờ: 50%
     *   - Muộn 3 giờ: 30%    - Muộn 6 giờ (hoặc từ 18:00 trở đi): 100% giá
     *     phòng (tính như thêm 1 đêm). Form chọn tới 6 giờ vẫn gửi được bình
     *     thường (chờ staff duyệt) — trên 6 giờ mới bắt xuống quầy trao đổi
     *     trực tiếp, khuyến nghị gia hạn hẳn 1 ngày thay vì trả phòng muộn
     *     (xem trang yêu cầu trả phòng muộn).
     */
    public static function calculateFee(float $hoursLate, bool $isAfterEighteen, float $lastNightTotal): float
    {
        if ($isAfterEighteen || $hoursLate > 5) {
            return round($lastNightTotal);
        }

        return round($lastNightTotal * $hoursLate * 0.10);
    }

    /**
     * Giá đêm cuối/phòng (1 đơn vị, không nhân quantity) của từng dòng đơn —
     * dùng chung cho lastNightTotal() và grantLateCheckout() (tính phí riêng
     * từng phòng). Mọi phòng cùng 1 dòng đơn (booking_item) có cùng giá.
     *
     * @return Collection<int, float>
     */
    private static function nightlyRateByItemId(Booking $booking): Collection
    {
        return $booking->bookingItems->mapWithKeys(function (BookingItem $item) {
            $breakdown = $item->price_breakdown ?? [];
            $lastNight = $breakdown !== [] ? (end($breakdown)['nightly_total'] ?? $item->price_per_night) : $item->price_per_night;

            return [$item->id => (float) $lastNight];
        });
    }

    /**
     * Giá đêm cuối cùng trong price_breakdown của đơn — dùng làm cơ sở tính
     * % phụ phí trả phòng muộn ở cả create() (khách xin phép trước), trang
     * xem trước phí (customer.bookings.late-checkout) và
     * BookingService::applyLateCheckoutSurchargeIfNeeded() (phí tự động dự
     * phòng khi khách không xin phép trước) — dùng chung 1 chỗ để 3 nơi luôn
     * ra cùng 1 kết quả.
     *
     * $selectedBookingItemRoomIds — truyền vào để chỉ tính trên đúng các
     * PHÒNG VẬT LÝ khách CHỌN trả muộn thay vì cả đơn (khác phí nhận phòng
     * sớm vốn cố định, phí này % giá phòng nên phải theo đúng phòng chọn).
     * Mọi phòng cùng 1 dòng đơn (booking_item) có cùng giá — dùng lại giá
     * đêm cuối của dòng đó cho từng phòng thuộc dòng (cùng cách tính ở
     * BookingService::computeRoomSettlementAmounts()). Bỏ trống (null, mặc
     * định) = cả đơn — dùng cho applyLateCheckoutSurchargeIfNeeded() (phí tự
     * động, không có bước chọn phòng) và trang xem trước phí trước khi khách
     * chọn xong.
     */
    public static function lastNightTotal(Booking $booking, ?array $selectedBookingItemRoomIds = null): float
    {
        if ($selectedBookingItemRoomIds === null) {
            return $booking->bookingItems->sum(function (BookingItem $item) {
                $breakdown = $item->price_breakdown ?? [];
                $lastNight = $breakdown !== [] ? (end($breakdown)['nightly_total'] ?? $item->price_per_night) : $item->price_per_night;

                return (float) $lastNight * $item->quantity;
            });
        }

        $nightlyRateByItemId = self::nightlyRateByItemId($booking);

        return $booking->bookingItems
            ->flatMap(fn (BookingItem $item) => $item->bookingItemRooms)
            ->whereIn('id', $selectedBookingItemRoomIds)
            ->sum(fn (BookingItemRoom $bir) => $nightlyRateByItemId[$bir->booking_item_id] ?? 0.0);
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
     * Trả về 1 dòng cho mỗi PHÒNG VẬT LÝ khách đã CHỌN trả muộn
     * (selectedBookingItemRooms()) — xem EarlyCheckinRequestService::usageOnDate()
     * cho quy ước tương tự bên chưa check-in (chọn theo số lượng, không có
     * phòng vật lý cụ thể).
     */
    public function usageOnDate(string $date): Collection
    {
        return LateCheckoutRequest::with(['booking.bookingItems.roomType', 'booking.bookingItems.bookingItemRooms.room'])
            ->where('status', 'approved')
            ->whereHas('booking', fn ($q) => $q
                ->whereDate('check_out', $date)
                ->where('status', '!=', BookingStatus::CANCELLED->value)
            )
            ->get()
            ->flatMap(fn (LateCheckoutRequest $request) => $request->selectedBookingItemRooms()->map(fn (BookingItemRoom $bir) => [
                'request'         => $request,
                'booking'         => $request->booking,
                'bookingItemRoom' => $bir,
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
