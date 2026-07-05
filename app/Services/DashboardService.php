<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\HotelInfo;
use App\Models\Payment;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Số liệu tổng quan dùng chung cho dashboard admin và staff — cùng một bộ
 * chỉ số vì cả hai đều cần nắm tình hình vận hành để xử lý booking/thanh toán.
 */
class DashboardService
{
    public function stats(): array
    {
        $totalBookings    = Booking::count();
        $cancelledBookings = Booking::where('status', 'cancelled')->count();

        return [
            'hotel_status'       => HotelInfo::instance()->status,
            'total_room_types'   => RoomType::count(),
            'active_room_types'  => RoomType::where('status', 'active')->count(),
            'total_customers'    => User::where('role', 'customer')->count(),
            'total_bookings'     => $totalBookings,
            'pending_bookings'   => Booking::where('status', 'pending')->count(),
            'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            'cancelled_bookings' => $cancelledBookings,
            'cancellation_rate'  => $totalBookings > 0 ? (int) round($cancelledBookings / $totalBookings * 100) : 0,
        ];
    }

    public function recentBookings(int $limit = 5)
    {
        return Booking::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Doanh thu N tháng gần nhất (tính bằng PHP thay vì SQL date function
     * để không phụ thuộc cú pháp riêng của SQLite/MySQL). Doanh thu gồm
     * thanh toán đủ (paid) và phần đã cọc (deposit_paid) — phần thực sự
     * đã thu về, chưa tính phần còn lại trả khi nhận phòng.
     *
     * @return array{labels: array<int, string>, totals: array<int, float>}
     */
    public function revenueByMonth(int $months = 6): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $buckets = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $buckets[now()->subMonths($i)->format('Y-m')] = 0.0;
        }

        Payment::where('status', PaymentStatus::PAID)
            ->where('paid_at', '>=', $start)
            ->get(['amount', 'paid_at'])
            ->each(function (Payment $payment) use (&$buckets) {
                $key = $payment->paid_at->format('Y-m');
                if (array_key_exists($key, $buckets)) {
                    $buckets[$key] += (float) $payment->amount;
                }
            });

        Payment::where('status', PaymentStatus::DEPOSIT_PAID)
            ->where('deposit_paid_at', '>=', $start)
            ->get(['deposit_amount', 'deposit_paid_at'])
            ->each(function (Payment $payment) use (&$buckets) {
                $key = $payment->deposit_paid_at->format('Y-m');
                if (array_key_exists($key, $buckets)) {
                    $buckets[$key] += (float) $payment->deposit_amount;
                }
            });

        return [
            'labels' => array_map(
                fn (string $key) => Carbon::createFromFormat('Y-m', $key)->translatedFormat('m/Y'),
                array_keys($buckets)
            ),
            'totals' => array_values($buckets),
        ];
    }

    /**
     * Tỷ lệ lấp đầy phòng hôm nay — tổng phòng đang bị giữ (pending/confirmed/
     * checked_in) so với tổng số phòng active. Dùng chung logic overlap với
     * RoomTypeService::adminIndexWithAvailability()/AvailabilityService.
     *
     * @return array{total: int, occupied: int, available: int, rate: int}
     */
    public function occupancyRate(): array
    {
        $today = now()->toDateString();

        $totalRooms = (int) RoomType::where('status', 'active')->sum('total_rooms');

        $occupied = (int) DB::table('booking_items')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->whereIn('bookings.status', BookingStatus::holdingStatuses())
            ->where('bookings.check_in', '<=', $today)
            ->where('bookings.check_out', '>', $today)
            ->sum('booking_items.quantity');

        $occupied = min($occupied, $totalRooms);

        return [
            'total'     => $totalRooms,
            'occupied'  => $occupied,
            'available' => max(0, $totalRooms - $occupied),
            'rate'      => $totalRooms > 0 ? (int) round($occupied / $totalRooms * 100) : 0,
        ];
    }
}
