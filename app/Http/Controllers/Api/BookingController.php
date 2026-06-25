<?php

namespace App\Http\Controllers\Api;

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
     * TODO (Tuần 11): truy vấn bookings theo user_id.
     */
    public function myBookings(Request $request): JsonResponse
    {
        return $this->success([], 'Chức năng đang phát triển (Tuần 11)');
    }

    /**
     * GET /api/v1/bookings/{booking}
     * Chi tiết một đơn đặt phòng của customer.
     * TODO (Tuần 11): kiểm tra đơn có thuộc về user không.
     */
    public function show(int $booking): JsonResponse
    {
        return $this->success([], 'Chức năng đang phát triển (Tuần 11)');
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
     * TODO (Tuần 11): kiểm tra điều kiện hủy, cập nhật trạng thái.
     */
    public function cancel(int $booking): JsonResponse
    {
        return $this->success([], 'Chức năng đang phát triển (Tuần 11)');
    }

    // ----------------------------------------------------------------
    // ADMIN / STAFF ROUTES
    // ----------------------------------------------------------------

    /**
     * GET /api/v1/admin/bookings
     * Danh sách tất cả đơn (admin/staff).
     * TODO (Tuần 12): filter theo trạng thái, ngày, khách hàng, khách sạn.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        return $this->success([], 'Chức năng đang phát triển (Tuần 12)');
    }

    /**
     * GET /api/v1/admin/bookings/{booking}
     * Chi tiết đơn (admin/staff).
     * TODO (Tuần 12).
     */
    public function adminShow(int $booking): JsonResponse
    {
        return $this->success([], 'Chức năng đang phát triển (Tuần 12)');
    }

    /**
     * PUT /api/v1/admin/bookings/{booking}/status
     * Admin/staff cập nhật trạng thái đơn.
     * TODO (Tuần 12): validate transition hợp lệ.
     */
    public function updateStatus(Request $request, int $booking): JsonResponse
    {
        return $this->success([], 'Chức năng đang phát triển (Tuần 12)');
    }

    /**
     * PUT /api/v1/admin/bookings/{booking}/payment
     * Admin/staff cập nhật trạng thái thanh toán mô phỏng.
     * TODO (Tuần 12): unpaid → paid → refunded.
     */
    public function updatePayment(Request $request, int $booking): JsonResponse
    {
        return $this->success([], 'Chức năng đang phát triển (Tuần 12)');
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

    // ----------------------------------------------------------------
    // PRIVATE
    // ----------------------------------------------------------------

    private function formatBooking($booking): array
    {
        return [
            'id'             => $booking->id,
            'booking_code'   => $booking->booking_code,
            'check_in'       => $booking->check_in->toDateString(),
            'check_out'      => $booking->check_out->toDateString(),
            'nights'         => $booking->nights,
            'customer_name'  => $booking->customer_name,
            'customer_phone' => $booking->customer_phone,
            'customer_email' => $booking->customer_email,
            'total_amount'   => $booking->total_amount,
            'status'         => $booking->status->value,
            'status_label'   => $booking->status->label(),
            'note'           => $booking->note,
            'payment'        => $booking->payment ? [
                'status'        => $booking->payment->status->value,
                'status_label'  => $booking->payment->status->label(),
                'method'        => $booking->payment->method->value,
                'method_label'  => $booking->payment->method->label(),
                'amount'        => $booking->payment->amount,
            ] : null,
            'created_at' => $booking->created_at->toIso8601String(),
        ];
    }
}
