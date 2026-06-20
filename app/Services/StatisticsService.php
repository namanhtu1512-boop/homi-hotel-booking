<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;

class StatisticsService
{
    public function overview(): array
    {
        return [
            'total_customers'    => User::where('role', 'customer')->count(),
            'total_bookings'     => Booking::count(),
            'bookings_today'     => Booking::whereDate('created_at', today())->count(),
            'pending_bookings'   => Booking::where('status', 'pending')->count(),
            'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            'cancelled_bookings' => Booking::where('status', 'cancelled')->count(),
        ];
    }

    public function bookingStats(string $from, string $to, string $groupBy = 'day'): array
    {
        $bookings = Booking::whereBetween('created_at', [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay(),
        ])->get();

        $total     = $bookings->count();
        $cancelled = $bookings->where('status', 'cancelled')->count();

        $grouped = $bookings->groupBy(function ($b) use ($groupBy) {
            return match ($groupBy) {
                'week'  => Carbon::parse($b->created_at)->startOfWeek()->format('Y-m-d'),
                'month' => Carbon::parse($b->created_at)->format('Y-m'),
                default => Carbon::parse($b->created_at)->format('Y-m-d'),
            };
        })->map(fn ($group) => [
            'total'     => $group->count(),
            'pending'   => $group->where('status', 'pending')->count(),
            'confirmed' => $group->where('status', 'confirmed')->count(),
            'cancelled' => $group->where('status', 'cancelled')->count(),
        ]);

        return [
            'total'       => $total,
            'cancel_rate' => $total > 0 ? round($cancelled / $total * 100, 1) : 0,
            'grouped'     => $grouped,
        ];
    }

    public function revenueStats(string $from, string $to): array
    {
        $payments = Payment::whereIn('status', ['paid', 'refunded'])
            ->whereBetween('paid_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ])->get();

        $totalRevenue  = $payments->where('status', 'paid')->sum('amount');
        $totalRefunded = $payments->where('status', 'refunded')->sum('amount');

        return [
            'total_revenue'  => $totalRevenue,
            'total_refunded' => $totalRefunded,
            'net_revenue'    => $totalRevenue - $totalRefunded,
        ];
    }
}
