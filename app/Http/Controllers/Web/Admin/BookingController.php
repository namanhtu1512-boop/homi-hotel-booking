<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\UpdatePaymentStatusRequest;
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
        $filters = $request->only(['status', 'booking_code', 'created_from', 'created_to']);

        $bookings = $this->bookingService->adminList($filters, 20)->appends($filters);

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'filters'  => $filters,
        ]);
    }

    public function show(int $id): View
    {
        return view('admin.bookings.show', [
            'booking' => $this->bookingService->findForAdmin($id),
        ]);
    }

    public function confirm(int $id): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->confirm($booking);

        $this->auditLog->log('booking.confirmed', $booking, "Xác nhận đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('admin.bookings.show', $id)
            ->with('success', "Đã xác nhận đơn {$booking->booking_code}.");
    }

    public function cancel(int $id): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->cancelByAdmin($booking);

        $this->auditLog->log('booking.cancelled', $booking, "Hủy đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('admin.bookings.show', $id)
            ->with('success', "Đã hủy đơn {$booking->booking_code}.");
    }

    public function updatePayment(int $id, UpdatePaymentStatusRequest $request): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->updatePaymentStatus($booking, $request->validated('status'));

        $this->auditLog->log('booking.payment_updated', $booking, "Cập nhật thanh toán đơn \"{$booking->booking_code}\" thành \"{$request->validated('status')}\".");

        return redirect()
            ->route('admin.bookings.show', $id)
            ->with('success', "Đã cập nhật trạng thái thanh toán đơn {$booking->booking_code}.");
    }
}
