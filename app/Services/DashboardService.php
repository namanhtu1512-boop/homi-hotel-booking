<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\HotelInfo;
use App\Models\Payment;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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

    /**
     * Số lượt đặt phòng & doanh thu trong khoảng [$from, $to], gộp theo
     * ngày/tháng/năm — trục thời gian dùng check_in (ngày khách nhận phòng)
     * chứ không phải ngày tạo đơn, để khớp với các nhóm thống kê khác trong
     * cùng bộ lọc (theo loại phòng, thời gian lưu trú...) vốn cũng lọc theo
     * check_in. Doanh thu ở đây là TỔNG GIÁ TRỊ đơn (total_amount) của đơn
     * chưa hủy, khác với revenueByMonth() ở trên vốn tính tiền THỰC THU qua
     * Payment — hai chỉ số phục vụ hai mục đích khác nhau (giá trị kinh
     * doanh phát sinh vs. dòng tiền thực tế).
     *
     * @return array{labels: array<int, string>, bookings: array<int, int>, revenue: array<int, float>}
     */
    public function bookingsRevenueByPeriod(Carbon $from, Carbon $to, string $granularity = 'day'): array
    {
        $format = match ($granularity) {
            'year'  => 'Y',
            'month' => 'Y-m',
            default => 'Y-m-d',
        };

        $buckets = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $buckets[$cursor->format($format)] = ['bookings' => 0, 'revenue' => 0.0];
            $cursor = match ($granularity) {
                'year'  => $cursor->addYear(),
                'month' => $cursor->addMonth(),
                default => $cursor->addDay(),
            };
        }

        Booking::where('status', '!=', BookingStatus::CANCELLED)
            ->whereBetween('check_in', [$from->toDateString(), $to->toDateString()])
            ->get(['check_in', 'total_amount'])
            ->each(function (Booking $booking) use (&$buckets, $format) {
                $key = $booking->check_in->format($format);
                if (array_key_exists($key, $buckets)) {
                    $buckets[$key]['bookings']++;
                    $buckets[$key]['revenue'] += (float) $booking->total_amount;
                }
            });

        $formatLabel = match ($granularity) {
            'year'  => fn (string $key) => $key,
            'month' => fn (string $key) => Carbon::createFromFormat('Y-m', $key)->translatedFormat('m/Y'),
            default => fn (string $key) => Carbon::createFromFormat('Y-m-d', $key)->format('d/m'),
        };

        return [
            'labels'   => array_map($formatLabel, array_keys($buckets)),
            'bookings' => array_map(fn (array $bucket) => $bucket['bookings'], array_values($buckets)),
            'revenue'  => array_map(fn (array $bucket) => $bucket['revenue'], array_values($buckets)),
        ];
    }

    /**
     * Số phòng vật lý thực sự có khách nhận phòng trong khoảng [$from, $to]
     * (dựa vào booking_item_rooms.checked_in_at — thời điểm nhận phòng thật,
     * không phải ngày đặt dự kiến), đếm không trùng lặp phòng.
     */
    public function roomsUsedInRange(Carbon $from, Carbon $to): int
    {
        return (int) DB::table('booking_item_rooms')
            ->whereBetween('checked_in_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->distinct()
            ->count('room_id');
    }

    /**
     * Thống kê theo từng loại phòng trong khoảng [$from, $to]: số lượt đặt
     * (dòng booking_items), số lượt thuê (tổng quantity) và doanh thu
     * (tổng subtotal) — lọc theo check_in để khớp trục thời gian với
     * bookingsRevenueByPeriod(). Sắp xếp giảm dần theo doanh thu, đứng đầu
     * danh sách coi như "loại phòng được đặt nhiều nhất".
     */
    public function roomTypeStats(Carbon $from, Carbon $to): Collection
    {
        return DB::table('booking_items')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->join('room_types', 'room_types.id', '=', 'booking_items.room_type_id')
            ->where('bookings.status', '!=', BookingStatus::CANCELLED->value)
            ->whereBetween('bookings.check_in', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('room_types.id, room_types.name, COUNT(*) as bookings_count, SUM(booking_items.quantity) as rooms_booked, SUM(booking_items.subtotal) as revenue')
            ->groupBy('room_types.id', 'room_types.name')
            ->orderByDesc('revenue')
            ->get();
    }

    /**
     * Thời gian lưu trú của các đơn chưa hủy có check_in trong khoảng
     * [$from, $to]: tổng/trung bình số đêm, và phân loại ngắn ngày
     * (<= 2 đêm) / dài ngày (>= 3 đêm) — mốc 2 đêm là ngưỡng phổ biến để
     * tách khách nghỉ cuối tuần/công tác ngắn khỏi khách nghỉ dưỡng dài ngày.
     */
    public function stayDurationStats(Carbon $from, Carbon $to): array
    {
        $nights = Booking::where('status', '!=', BookingStatus::CANCELLED)
            ->whereBetween('check_in', [$from->toDateString(), $to->toDateString()])
            ->pluck('nights');

        $totalBookings = $nights->count();
        $totalNights = (int) $nights->sum();
        $shortStay = $nights->filter(fn ($n) => $n <= 2)->count();

        return [
            'total_bookings'    => $totalBookings,
            'total_nights'      => $totalNights,
            'average_nights'    => $totalBookings > 0 ? round($totalNights / $totalBookings, 1) : 0,
            'short_stay_count'  => $shortStay,
            'long_stay_count'   => $totalBookings - $shortStay,
        ];
    }

    /**
     * Tỷ lệ lấp đầy trong khoảng [$from, $to]: số đêm-phòng đã có khách
     * (overlap giữa [check_in, check_out) của từng đơn với khoảng lọc, nhân
     * quantity) so với tổng số đêm-phòng có thể bán (total_rooms * số ngày
     * trong khoảng). Có thể lọc theo 1 loại phòng cụ thể qua $roomTypeId.
     * Tính cả đơn đã checked_out/completed (không chỉ đơn đang giữ phòng)
     * vì đây là thống kê NHÌN LẠI một khoảng thời gian, khác occupancyRate()
     * ở trên vốn chỉ cần biết tình trạng NGAY HÔM NAY.
     *
     * @return array{occupied_nights: int, capacity_nights: int, rate: int}
     */
    public function occupancyForRange(Carbon $from, Carbon $to, ?int $roomTypeId = null): array
    {
        $rangeStart = $from->copy()->startOfDay();
        $rangeEndExclusive = $to->copy()->startOfDay()->addDay();
        $days = $rangeStart->diffInDays($rangeEndExclusive);

        $query = DB::table('booking_items')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->whereIn('bookings.status', [
                ...BookingStatus::holdingStatuses(),
                BookingStatus::CHECKED_OUT->value,
                BookingStatus::COMPLETED->value,
            ])
            ->where('bookings.check_in', '<', $rangeEndExclusive)
            ->where('bookings.check_out', '>', $rangeStart);

        if ($roomTypeId) {
            $query->where('booking_items.room_type_id', $roomTypeId);
        }

        $occupiedNights = $query->get(['bookings.check_in', 'bookings.check_out', 'booking_items.quantity'])
            ->sum(function ($item) use ($rangeStart, $rangeEndExclusive) {
                $checkIn = Carbon::parse($item->check_in);
                $checkOut = Carbon::parse($item->check_out);

                $overlapStart = $checkIn->gt($rangeStart) ? $checkIn : $rangeStart;
                $overlapEnd = $checkOut->lt($rangeEndExclusive) ? $checkOut : $rangeEndExclusive;

                return max(0, $overlapStart->diffInDays($overlapEnd)) * $item->quantity;
            });

        $totalRoomsQuery = RoomType::where('status', 'active');
        if ($roomTypeId) {
            $totalRoomsQuery->where('id', $roomTypeId);
        }
        $totalRooms = (int) $totalRoomsQuery->sum('total_rooms');
        $capacityNights = $totalRooms * $days;

        return [
            'occupied_nights' => (int) $occupiedNights,
            'capacity_nights' => $capacityNights,
            'rate'            => $capacityNights > 0 ? (int) round($occupiedNights / $capacityNights * 100) : 0,
        ];
    }

    /**
     * Tỷ lệ lấp đầy trong khoảng [$from, $to] của TỪNG loại phòng đang hoạt
     * động — dùng để so sánh công suất giữa các loại phòng trong cùng kỳ.
     *
     * @return Collection<int, array{id: int, name: string, rate: int}>
     */
    public function occupancyByRoomType(Carbon $from, Carbon $to): Collection
    {
        return RoomType::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (RoomType $roomType) => [
                'id'   => $roomType->id,
                'name' => $roomType->name,
                'rate' => $this->occupancyForRange($from, $to, $roomType->id)['rate'],
            ]);
    }

    /**
     * Danh sách tiện nghi của từng loại phòng đang hoạt động, kèm số lượng
     * loại phòng sở hữu mỗi tiện nghi — dùng để so sánh tiện nghi giữa các
     * loại phòng (không phụ thuộc khoảng thời gian lọc, vì tiện nghi là
     * thuộc tính cố định của loại phòng, không phát sinh theo đơn đặt).
     *
     * @return array{room_types: Collection, amenities: Collection}
     */
    public function amenityStats(): array
    {
        $roomTypes = RoomType::where('status', 'active')
            ->with('amenities')
            ->orderBy('name')
            ->get();

        $amenities = Amenity::withCount(['roomTypes' => fn ($query) => $query->where('status', 'active')])
            ->orderByDesc('room_types_count')
            ->get();

        return [
            'room_types' => $roomTypes,
            'amenities'  => $amenities,
        ];
    }
}
