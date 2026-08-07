<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\HotelInfoService;
use App\Services\PricingService;
use App\Services\RoomHoldService;
use App\Services\RoomTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly RoomTypeService $roomTypeService,
        private readonly AvailabilityService $availabilityService,
        private readonly RoomHoldService $roomHoldService,
        private readonly PricingService $pricingService,
        private readonly HotelInfoService $hotelInfoService,
    ) {}

    public function create(Request $request): View
    {
        // Vào từ trang chi tiết phòng (?room_type_id=...) — 404 nếu phòng không
        // active để giữ hành vi cũ (không cho đặt phòng ẩn/bảo trì/đã xóa).
        if ($request->filled('room_type_id')) {
            $this->roomTypeService->findActive((int) $request->query('room_type_id'));
        }

        $roomTypes = $this->roomTypeService->list();

        // Giá "tạm tính" phía JS (updateEstimate() trong blade) trước đây chỉ
        // dùng giá gốc, gây lệch số với khối "Kiểm tra phòng trống" bên dưới
        // (đã tính đúng giá theo mùa + cuối tuần qua PricingService::calculate())
        // khi có đợt giá đang active hôm nay hoặc đêm nay là cuối tuần — dùng
        // previewTonight() (gọi thẳng calculate()) để 2 nơi luôn khớp nhau
        // trong trường hợp phổ biến (không đảm bảo khớp 100% khi kỳ nghỉ nhiều
        // đêm vắt qua nhiều mức giá khác nhau — JS vẫn chỉ là ước tính nhanh).
        $todayPrices = $roomTypes->mapWithKeys(fn ($type) => [
            $type->id => $this->pricingService->previewTonight($type)['preview_price'],
        ]);

        $checkIn  = $request->query('check_in');
        $checkOut = $request->query('check_out');

        // Danh sách dòng loại phòng khách đang chọn (để repopulate form):
        // ưu tiên `items[]` từ lần "Kiểm tra phòng trống"; nếu vào từ trang chi
        // tiết phòng thì tạo sẵn 1 dòng cho ?room_type_id. Mặc định 1 dòng trống.
        $items = $this->normalizeItems($request);

        // Tuần 9 (Sprint 5) — nút "Kiểm tra phòng trống": resubmit GET kèm items
        // + ngày để hiển thị available_quantity/can_book cho từng loại phòng
        // trước khi khách đặt thật.
        $availabilities = [];
        $holdExpiresAt  = null;
        $sessionId      = $request->session()->getId();

        if ($checkIn && $checkOut) {
            foreach ($items as $item) {
                if (empty($item['room_type_id'])) {
                    continue;
                }

                $roomType = $roomTypes->firstWhere('id', (int) $item['room_type_id']);
                $quantity = max(1, (int) ($item['quantity'] ?? 1));

                try {
                    $result = $this->availabilityService->check(
                        (int) $item['room_type_id'], $checkIn, $checkOut, $quantity, $sessionId
                    );

                    // Tính giá thật (gồm điều chỉnh theo mùa, tăng lẫn giảm) để
                    // hiển thị ngay khi khách kiểm tra phòng trống — tránh giá
                    // tạm tính phía JS (chỉ dựa trên giá gốc) làm khách hiểu nhầm.
                    // extraBeds chỉ là ƯỚC TÍNH hiển thị (1/phòng nếu tick) — số
                    // thật sự được CẤP do BookingService::create() quyết theo tồn
                    // kho lúc đặt, có thể khác nếu giường phụ hết ngay lúc đó.
                    $extraBeds = (! empty($item['extra_bed']) && $roomType?->supportsExtraBed()) ? $quantity : 0;
                    $pricing = $roomType
                        ? $this->pricingService->calculate(
                            $roomType, $checkIn, $checkOut, $quantity, max(0, (int) ($item['children'] ?? 0)), $extraBeds
                        )
                        : null;

                    $availabilities[] = [
                        'name'    => $roomType?->name ?? "Loại phòng #{$item['room_type_id']}",
                        'result'  => $result,
                        'error'   => null,
                        'pricing' => $pricing,
                    ];
                } catch (ValidationException $e) {
                    $availabilities[] = [
                        'name'    => $roomType?->name ?? "Loại phòng #{$item['room_type_id']}",
                        'result'  => null,
                        'error'   => collect($e->errors())->flatten()->first(),
                        'pricing' => null,
                    ];
                }
            }

            // Giữ chỗ tạm thời cho các dòng phòng vừa kiểm tra trong khi
            // khách điền nốt thông tin — hết hạn sau RoomHoldService::TTL_MINUTES.
            $holdExpiresAt = $this->roomHoldService->createForSession($sessionId, $items, $checkIn, $checkOut);
        }

        return view('customer.booking.create', [
            'roomTypes'                 => $roomTypes,
            'items'                     => $items,
            'checkIn'                   => $checkIn,
            'checkOut'                  => $checkOut,
            'availabilities'            => $availabilities,
            'holdExpiresAt'             => $holdExpiresAt,
            'todayPrices'               => $todayPrices,
            'extraBedSurchargePerNight' => (int) $this->hotelInfoService->current()->extra_bed_surcharge_per_night,
        ]);
    }

    /**
     * Chuẩn hóa danh sách dòng loại phòng từ query cho form đặt phòng —
     * mỗi dòng mang theo số khách riêng (adults/children) vì sức chứa được
     * validate theo từng loại phòng, không gộp chung cho cả đơn.
     *
     * @return array<int, array{room_type_id: mixed, quantity: int, adults: int, children: int}>
     */
    private function normalizeItems(Request $request): array
    {
        $rawItems = $request->query('items');

        if (is_array($rawItems) && $rawItems !== []) {
            return array_values(array_map(fn ($item) => [
                'room_type_id' => $item['room_type_id'] ?? null,
                'quantity'     => max(1, (int) ($item['quantity'] ?? 1)),
                'adults'       => max(1, (int) ($item['adults'] ?? 1)),
                'children'     => max(0, (int) ($item['children'] ?? 0)),
                'extra_bed'    => ! empty($item['extra_bed']),
            ], $rawItems));
        }

        return [[
            'room_type_id' => $request->query('room_type_id'),
            'quantity'     => max(1, (int) $request->query('quantity', 1)),
            'adults'       => max(1, (int) $request->query('adults', 1)),
            'children'     => max(0, (int) $request->query('children', 0)),
            'extra_bed'    => $request->boolean('extra_bed'),
        ]];
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $data                       = $request->validated();
        $data['_hold_session_id']   = $request->session()->getId();

        $booking = $this->bookingService->create($request->user(), $data);

        return redirect()
            ->route('customer.bookings.show', $booking->id)
            ->with('success', "Đặt phòng thành công! Mã đơn: {$booking->booking_code}.");
    }

    public function index(Request $request): View
    {
        $bookings = $this->bookingService->myBookings(
            $request->user(),
            $request->only('status'),
        )->appends($request->only('status'));

        return view('customer.bookings.index', ['bookings' => $bookings]);
    }

    public function show(int $id, Request $request): View
    {
        return view('customer.bookings.show', [
            'booking' => $this->bookingService->findForCustomer($id, $request->user()),
        ]);
    }

    public function invoice(int $id, Request $request): View
    {
        return view('bookings.invoice', [
            'booking'   => $this->bookingService->findForCustomer($id, $request->user()),
            'backRoute' => route('customer.bookings.show', $id),
        ]);
    }

    public function cancel(int $id, Request $request): RedirectResponse
    {
        $result = $this->bookingService->cancelByCustomer($id, $request->user());

        if (! $result['refund_ok']) {
            return redirect()
                ->route('customer.bookings.show', $id)
                ->with('error', 'Đã hủy đơn, nhưng hoàn tiền tự động không thành công. Khách sạn sẽ liên hệ để hoàn tiền thủ công.');
        }

        return redirect()
            ->route('customer.bookings.show', $id)
            ->with('success', 'Đã hủy đơn đặt phòng.');
    }

    public function payOnline(int $id, Request $request): RedirectResponse
    {
        $result = $this->bookingService->initiateVnpayPayment($id, $request->user(), $request->ip());

        return redirect()->away($result['payment_url']);
    }

    public function payBankTransfer(int $id, Request $request): RedirectResponse
    {
        $booking = $this->bookingService->markBankTransferPending($id, $request->user());

        return redirect()
            ->route('customer.bookings.show', $booking->id)
            ->with('success', 'Đã ghi nhận, chờ khách sạn xác nhận chuyển khoản.');
    }

    public function payDeposit(int $id, Request $request): RedirectResponse
    {
        $booking = $this->bookingService->payDepositDemo($id, $request->user());

        $deposit   = number_format($booking->depositAmount(), 0, ',', '.');
        $remaining = number_format($booking->remainingAfterDeposit(), 0, ',', '.');

        return redirect()
            ->route('customer.bookings.show', $booking->id)
            ->with('success', "Đã đặt cọc {$deposit}đ. Vui lòng thanh toán {$remaining}đ còn lại bằng tiền mặt khi nhận phòng.");
    }
}
