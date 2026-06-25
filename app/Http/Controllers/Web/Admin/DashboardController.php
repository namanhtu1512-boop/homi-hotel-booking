<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\HotelInfo;
use App\Models\RoomType;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'hotel_status'       => HotelInfo::instance()->status,
            'total_room_types'   => RoomType::count(),
            'active_room_types'  => RoomType::where('status', 'active')->count(),
            'total_customers'    => User::where('role', 'customer')->count(),
            'total_bookings'     => Booking::count(),
            'pending_bookings'   => Booking::where('status', 'pending')->count(),
            'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            'cancelled_bookings' => Booking::where('status', 'cancelled')->count(),
        ];

        $recentBookings = Booking::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentBookings'));
    }
}
