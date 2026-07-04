<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\HotelInfo;
use App\Models\RoomType;
use App\Models\User;

/**
 * Số liệu tổng quan dùng chung cho dashboard admin và staff — cùng một bộ
 * chỉ số vì cả hai đều cần nắm tình hình vận hành để xử lý booking/thanh toán.
 */
class DashboardService
{
    public function stats(): array
    {
        return [
            'hotel_status'       => HotelInfo::instance()->status,
            'total_room_types'   => RoomType::count(),
            'active_room_types'  => RoomType::where('status', 'active')->count(),
            'total_customers'    => User::where('role', 'customer')->count(),
            'total_bookings'     => Booking::count(),
            'pending_bookings'   => Booking::where('status', 'pending')->count(),
            'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            'cancelled_bookings' => Booking::where('status', 'cancelled')->count(),
        ];
    }

    public function recentBookings(int $limit = 5)
    {
        return Booking::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
