<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdatePaymentStatusRequest;
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
        $bookingModel = $this->bookingService->cancelByCustomer($booking, $request->user());

        return $this->success($this->formatBooking($bookingModel), 'Đã hủy đơn.');
    }

    // ----------------------------------------------------------------
    // ADMIN / STAFF ROUTES
    // ----------------------------------------------------------------

    /**
     * GET /api/v1/admin/bookings
     * Danh sách tất cả đơn (admin/staff) — tái dùng nguyên bộ filter đã có
     * ở BookingService::adminList() (status, payment_status, customer_name,
     * room_type_id, created_from/to, check_in_from/to...).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->adminList($request->all());

        return $this->success($bookings->through(fn ($booking) => $this->formatBooking($booking)));
    }

    /**
     * GET /api/v1/admin/bookings/{booking}
     * Chi tiết đơn (admin/staff).
     */
    public function adminShow(int $booking): JsonResponse
    {
        $bookingModel = $this->bookingService->findForAdmin($booking);

        return $this->success($this->formatBooking($bookingModel));
    }

    /**
     * PUT /api/v1/admin/bookings/{booking}/status
     * Admin/staff xác nhận/hủy/hoàn thành đơn — chỉ 3 hướng chuyển hợp lệ
     * này được phép qua API, khớp đúng 3 action riêng biệt bên Blade
     * (confirm/cancel/complete). Rule chuyển trạng thái hợp lệ nằm ở
     * BookingService (canConfirm/canCancelByAdmin/canComplete).
     */
    public function updateStatus(Request $request, int $booking): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:confirmed,cancelled,completed'],
        ], [], [
            'status' => 'trạng thái',
        ]);

        $bookingModel = $this->bookingService->findForAdmin($booking);

        $updated = match ($data['status']) {
            'confirmed' => $this->bookingService->confirm($bookingModel),
            'cancelled' => $this->bookingService->cancelByAdmin($bookingModel),
            'completed' => $this->bookingService->complete($bookingModel),
        };

        return $this->success($this->formatBooking($updated), 'Cập nhật trạng thái thành công.');
    }

    /**
     * PUT /api/v1/admin/bookings/{booking}/payment
     * Admin/staff cập nhật trạng thái thanh toán mô phỏng (unpaid → paid,
     * paid → refunded) — tái dùng UpdatePaymentStatusRequest đã dùng ở Blade.
     */
    public function updatePayment(UpdatePaymentStatusRequest $request, int $booking): JsonResponse
    {
        $bookingModel = $this->bookingService->findForAdmin($booking);

        $updated = $this->bookingService->updatePaymentStatus($bookingModel, $request->validated('status'));

        return $this->success($this->formatBooking($updated), 'Cập nhật thanh toán thành công.');
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
            'id'              => $booking->id,
            'booking_code'    => $booking->booking_code,
            'check_in'        => $booking->check_in->toDateString(),
            'check_out'       => $booking->check_out->toDateString(),
            'nights'          => $booking->nights,
            'customer_name'   => $booking->customer_name,
            'customer_phone'  => $booking->customer_phone,
            'customer_email'  => $booking->customer_email,
            'total_amount'    => $booking->total_amount,
            'discount_amount' => $booking->discount_amount,
            'status'          => $booking->status->value,
            'status_label'    => $booking->status->label(),
            'note'            => $booking->note,
            'items'           => $booking->bookingItems->map(fn ($item) => [
                'room_type_id'    => $item->room_type_id,
                'room_type_name'  => $item->roomType?->name,
                'quantity'        => $item->quantity,
                'adults'          => $item->adults,
                'children'        => $item->children,
                'price_per_night' => $item->price_per_night,
                'nights'          => $item->nights,
                'subtotal'        => $item->subtotal,
                'child_surcharge' => $item->child_surcharge,
                'price_breakdown' => $item->price_breakdown,
            ])->all(),
            'payment' => $booking->payment ? [
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
