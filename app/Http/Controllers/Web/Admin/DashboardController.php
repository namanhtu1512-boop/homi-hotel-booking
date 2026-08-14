<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(Request $request)
    {
        $stats          = $this->dashboardService->stats();
        $recentBookings = $this->dashboardService->recentBookings();
        $revenue        = $this->dashboardService->revenueByMonth();
        $occupancy      = $this->dashboardService->occupancyRate();

        [$from, $to, $granularity] = $this->resolvePeriodFilter($request);

        $periodLength = $from->diffInDays($to) + 1;
        $previousTo   = $from->copy()->subDay();
        $previousFrom = $previousTo->copy()->subDays($periodLength - 1);

        $periodStats        = $this->dashboardService->bookingsRevenueByPeriod($from, $to, $granularity);
        $roomsUsed          = $this->dashboardService->roomsUsedInRange($from, $to);
        $roomTypeStats      = $this->dashboardService->roomTypeStats($from, $to);
        $stayDuration       = $this->dashboardService->stayDurationStats($from, $to);
        $occupancyRange     = $this->dashboardService->occupancyForRange($from, $to);
        $previousOccupancy  = $this->dashboardService->occupancyForRange($previousFrom, $previousTo);
        $occupancyByType    = $this->dashboardService->occupancyByRoomType($from, $to);
        $amenityStats       = $this->dashboardService->amenityStats();

        $filters = [
            'from'   => $from->toDateString(),
            'to'     => $to->toDateString(),
            'period' => $granularity,
        ];

        return view('admin.dashboard', compact(
            'stats',
            'recentBookings',
            'revenue',
            'occupancy',
            'filters',
            'periodStats',
            'roomsUsed',
            'roomTypeStats',
            'stayDuration',
            'occupancyRange',
            'previousOccupancy',
            'occupancyByType',
            'amenityStats',
        ));
    }

    /**
     * Khoảng thời gian & mức gộp (ngày/tháng/năm) cho khối "Thống kê chi
     * tiết" — mặc định là tháng hiện tại, gộp theo ngày. Tự nới mức gộp nếu
     * khoảng lọc quá dài để tránh biểu đồ có hàng trăm/nghìn điểm không đọc
     * được (vd: lọc theo ngày cho khoảng 2 năm).
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolvePeriodFilter(Request $request): array
    {
        $to   = $request->filled('to') ? Carbon::parse($request->input('to')) : now();
        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : now()->startOfMonth();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $granularity = $request->input('period', 'day');
        if (! in_array($granularity, ['day', 'month', 'year'], true)) {
            $granularity = 'day';
        }

        $days = $from->diffInDays($to);
        if ($granularity === 'day' && $days > 92) {
            $granularity = 'month';
        }
        if ($granularity === 'month' && $days > 730) {
            $granularity = 'year';
        }

        return [$from->startOfDay(), $to->startOfDay(), $granularity];
    }
}
