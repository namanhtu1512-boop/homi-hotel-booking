<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsBookingForApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;
    use FormatsBookingForApi;

    public function __construct(
        private BookingService $bookingService,
        private AvailabilityService $availabilityService,
    ) {}

    // ----------------------------------------------------------------
    // CUSTOMER ROUTES
    // ----------------------------------------------------------------

    /**
     * GET /api/v1/bookings
     * Danh sách đơn đặt phòng của customer đang đăng nhập.
     */
    public function myBookings(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->myBookings($request->user(), $request->only('status'));

        return $this->success($bookings->through(fn ($booking) => $this->formatBooking($booking)));
    }

    /**
     * GET /api/v1/bookings/{booking}
     * Chi tiết một đơn đặt phòng của customer — findForCustomer() đã tự
     * kiểm tra quyền sở hữu (Gate::authorize), 403/404 render JSON tự động.
     */
    public function show(int $booking, Request $request): JsonResponse
    {
        $bookingModel = $this->bookingService->findForCustomer($booking, $request->user());

        return $this->success($this->formatBooking($bookingModel));
    }

    /**
     * POST /api/v1/bookings
     * Tạo đơn đặt phòng mới.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->create(
            $request->user(),
            $request->validated()
        );

        return $this->created(
            $this->formatBooking($booking),
            'Đặt phòng thành công.'
        );
    }

    /**
     * POST /api/v1/bookings/{booking}/cancel
     * Khách hủy đơn của chính mình.
     */
    public function cancel(int $booking, Request $request): JsonResponse
    {
        $result = $this->bookingService->cancelByCustomer($booking, $request->user());

        $message = $result['refund_ok']
            ? 'Đã hủy đơn.'
            : 'Đã hủy đơn, nhưng hoàn tiền tự động không thành công — cần xử lý hoàn tiền thủ công.';

        return $this->success($this->formatBooking($result['booking']), $message);
    }

    // ----------------------------------------------------------------
    // PUBLIC ROUTES
    // ----------------------------------------------------------------

    /**
     * GET /api/v1/room-types/{roomType}/availability
     * Kiểm tra phòng trống theo ngày (public, không cần auth).
     */
    public function checkAvailability(Request $request, int $roomType): JsonResponse
    {
        $data = $request->validate([
            'check_in'  => ['required', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date_format:Y-m-d'],
            'quantity'  => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $result = $this->availabilityService->check(
            $roomType,
            $data['check_in'],
            $data['check_out'],
            (int) ($data['quantity'] ?? 1),
        );

        return $this->success($result);
    }
}
