<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index()
    {
        $stats          = $this->dashboardService->stats();
        $recentBookings = $this->dashboardService->recentBookings();
        $revenue        = $this->dashboardService->revenueByMonth();
        $occupancy      = $this->dashboardService->occupancyRate();

        return view('admin.dashboard', compact('stats', 'recentBookings', 'revenue', 'occupancy'));
    }
}
