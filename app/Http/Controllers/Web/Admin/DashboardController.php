<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotel;
use App\Services\StatisticsService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly StatisticsService $statisticsService) {}

    public function index(): View
    {
        $stats = $this->statisticsService->overview();
        $stats['total_revenue'] = Booking::where('status', '!=', 'cancelled')->sum('total_amount');

        $recentBookings = Booking::with('hotel')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $topHotels = Hotel::query()
            ->where('status', 'active')
            ->withSum(['bookings as revenue' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total_amount')
            ->withCount(['bookings as bookings_count' => fn ($q) => $q->where('status', '!=', 'cancelled')])
            ->orderByDesc('revenue')
            ->limit(3)
            ->get();

        $days = collect(range(6, 0))->map(fn ($i) => Carbon::today()->subDays($i));
        $chartLabels = $days->map(fn ($d) => $d->format('d/m'))->values()->all();
        $chartValues = $days->map(fn ($d) => Booking::whereDate('created_at', $d)->count())->values()->all();

        $statusColors = [
            'pending'     => 'var(--orange)',
            'confirmed'   => 'var(--blue)',
            'checked_in'  => '#4499ff',
            'checked_out' => '#bb88ff',
            'completed'   => 'var(--green)',
            'cancelled'   => 'var(--red)',
        ];

        $donutSegments = Booking::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($count, $status) => [
                'label' => BookingStatus::from($status)->label(),
                'count' => $count,
                'color' => $statusColors[$status] ?? 'var(--muted)',
            ])
            ->values()
            ->all();

        return view('admin.dashboard', compact(
            'stats', 'recentBookings', 'topHotels', 'chartLabels', 'chartValues', 'donutSegments'
        ));
    }
}
