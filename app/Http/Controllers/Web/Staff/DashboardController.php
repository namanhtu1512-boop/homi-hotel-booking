<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index()
    {
        $stats = $this->dashboardService->stats();
        $recentBookings = $this->dashboardService->recentBookings();

        return view('staff.dashboard', compact('stats', 'recentBookings'));
    }
}
