<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\AuditLogService;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $bookings = $this->bookingService->adminList(
            filters: $request->only(['search', 'status']),
            perPage: 10,
        )->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'search'   => $request->input('search', ''),
            'status'   => $request->input('status', ''),
        ]);
    }

    public function show(Booking $booking): View
    {
        $booking->load(['user', 'hotel', 'bookingItems.roomType', 'payment', 'statusLogs']);

        return view('admin.bookings.show', compact('booking'));
    }

    public function confirm(Booking $booking): RedirectResponse
    {
        $this->bookingService->confirm($booking);

        $this->auditLog->log('booking.confirmed', $booking, "Xác nhận đặt phòng \"{$booking->booking_code}\".");

        return redirect()
            ->back()
            ->with('success', "Đã xác nhận đặt phòng \"{$booking->booking_code}\".");
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        $this->bookingService->cancelByAdmin($booking);

        $this->auditLog->log('booking.cancelled', $booking, "Hủy đặt phòng \"{$booking->booking_code}\".");

        return redirect()
            ->back()
            ->with('success', "Đã hủy đặt phòng \"{$booking->booking_code}\".");
    }

    public function checkIn(Booking $booking): RedirectResponse
    {
        $this->bookingService->checkIn($booking);

        $this->auditLog->log('booking.checked_in', $booking, "Check-in đặt phòng \"{$booking->booking_code}\".");

        return redirect()
            ->back()
            ->with('success', "Đã check-in cho đặt phòng \"{$booking->booking_code}\".");
    }

    public function checkOut(Booking $booking): RedirectResponse
    {
        $this->bookingService->checkOut($booking);

        $this->auditLog->log('booking.checked_out', $booking, "Check-out đặt phòng \"{$booking->booking_code}\".");

        return redirect()
            ->back()
            ->with('success', "Đã check-out cho đặt phòng \"{$booking->booking_code}\".");
    }
}
