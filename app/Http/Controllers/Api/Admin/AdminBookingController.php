<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\FormatsBookingForApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\UpdatePaymentStatusRequest;
use App\Services\BookingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    use ApiResponse;
    use FormatsBookingForApi;

    public function __construct(private BookingService $bookingService) {}

    /**
     * GET /api/v1/admin/bookings
     * Danh sách tất cả đơn — tái dùng nguyên bộ filter đã có ở
     * BookingService::adminList() (status, payment_status, customer_name,
     * room_type_id, created_from/to, check_in_from/to...).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->adminList($request->all());

        return $this->success($bookings->through(fn ($booking) => $this->formatBooking($booking)));
    }

    /**
     * GET /api/v1/admin/bookings/{booking}
     */
    public function adminShow(int $booking): JsonResponse
    {
        $bookingModel = $this->bookingService->findForAdmin($booking);

        return $this->success($this->formatBooking($bookingModel));
    }

    /**
     * PUT /api/v1/admin/bookings/{booking}/status
     * Xác nhận/hủy/hoàn thành đơn — chỉ 3 hướng chuyển hợp lệ này được phép
     * qua API, khớp đúng 3 action riêng biệt bên Blade
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

        if ($data['status'] === 'cancelled') {
            $result = $this->bookingService->cancelByAdmin($bookingModel);

            $message = $result['refund_ok']
                ? 'Cập nhật trạng thái thành công.'
                : 'Đã hủy đơn, nhưng hoàn tiền tự động không thành công — cần xử lý hoàn tiền thủ công.';

            return $this->success($this->formatBooking($result['booking']), $message);
        }

        $updated = match ($data['status']) {
            'confirmed' => $this->bookingService->confirm($bookingModel),
            'completed' => $this->bookingService->complete($bookingModel),
        };

        return $this->success($this->formatBooking($updated), 'Cập nhật trạng thái thành công.');
    }

    /**
     * PUT /api/v1/admin/bookings/{booking}/payment
     * Cập nhật trạng thái thanh toán mô phỏng (unpaid → paid, paid →
     * refunded) — tái dùng UpdatePaymentStatusRequest đã dùng ở Blade.
     */
    public function updatePayment(UpdatePaymentStatusRequest $request, int $booking): JsonResponse
    {
        $bookingModel = $this->bookingService->findForAdmin($booking);

        $updated = $this->bookingService->updatePaymentStatus($bookingModel, $request->validated('status'));

        return $this->success($this->formatBooking($updated), 'Cập nhật thanh toán thành công.');
    }
}
