<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * BookingPolicy — kiểm soát quyền xem/hủy đơn đặt phòng ở khu vực customer.
 *
 * Quy tắc:
 *  - customer chỉ xem/hủy được đơn của chính mình (so khớp user_id).
 *  - admin/staff không dùng route customer để thao tác đơn — họ có luồng
 *    /admin/bookings riêng (AdminBookingManagementTest), nên policy này chỉ
 *    áp dụng cho chủ sở hữu đơn, không mở quyền cho admin/staff.
 *
 * Điều kiện nghiệp vụ "đơn ở trạng thái nào mới được hủy" (pending/confirmed,
 * chưa tới ngày check-in) nằm ở Booking::canCancelByCustomer() vì đó là rule
 * nghiệp vụ theo trạng thái dữ liệu, không phải rule phân quyền.
 *
 * Policy được Laravel tự động discover theo quy ước:
 *   Model Booking → app/Policies/BookingPolicy.php
 */
class BookingPolicy
{
    /**
     * Xem chi tiết đơn (GET /customer/bookings/{id}).
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }

    /**
     * Hủy đơn (POST /customer/bookings/{id}/cancel).
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }
}
