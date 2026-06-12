<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;

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
     * TODO (Tuần 10): gọi AvailabilityService + PricingService + BookingService.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->success([], 'Chức năng đang phát triển (Tuần 10)');
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
     * GET /api/v1/hotels/{hotel}/availability
     * Kiểm tra phòng trống theo ngày (public, không cần auth).
     * TODO (Tuần 9): code AvailabilityService xử lý overlap.
     */
    public function checkAvailability(Request $request, int $hotel): JsonResponse
    {
        return $this->success([], 'Chức năng đang phát triển (Tuần 9)');
    }
}
