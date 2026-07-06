<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\UpdatePaymentStatusRequest;
use App\Models\RoomType;
use App\Services\AuditLogService;
use App\Services\BookingService;
use App\Services\RoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly AuditLogService $auditLog,
        private readonly RoomService $roomService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'status',
            'payment_status',
            'booking_code',
            'customer_name',
            'created_from',
            'created_to',
            'check_in_from',
            'check_in_to',
            'room_type_id',
        ]);

        $bookings = $this->bookingService->adminList($filters, 20)->appends($filters);

        return view('staff.bookings.index', [
            'bookings'  => $bookings,
            'filters'   => $filters,
            'roomTypes' => RoomType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(int $id): View
    {
        return view('staff.bookings.show', [
            'booking' => $this->bookingService->findForAdmin($id),
        ]);
    }

    public function invoice(int $id): View
    {
        return view('bookings.invoice', [
            'booking'   => $this->bookingService->findForAdmin($id),
            'backRoute' => route('staff.bookings.show', $id),
        ]);
    }

    public function confirm(int $id): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->confirm($booking);

        $this->auditLog->log('booking.confirmed', $booking, "Xác nhận đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('staff.bookings.show', $id)
            ->with('success', "Đã xác nhận đơn {$booking->booking_code}.");
    }

    public function cancel(int $id): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->cancelByAdmin($booking);

        $this->auditLog->log('booking.cancelled', $booking, "Hủy đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('staff.bookings.show', $id)
            ->with('success', "Đã hủy đơn {$booking->booking_code}.");
    }

    public function showCheckIn(int $id): View
    {
        $booking = $this->bookingService->findForAdmin($id);

        $availableRooms = $booking->bookingItems->mapWithKeys(
            fn ($item) => [$item->id => $this->roomService->availableForRoomType($item->room_type_id)]
        );

        return view('bookings.check-in', [
            'booking'        => $booking,
            'availableRooms' => $availableRooms,
            'formAction'     => route('staff.bookings.check-in', $id),
            'backRoute'      => route('staff.bookings.show', $id),
            'layout'         => 'layouts.staff',
        ]);
    }

    public function checkIn(int $id, Request $request): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);

        $roomAssignments = collect($request->input('rooms', []))
            ->map(fn ($roomIds) => array_map('intval', (array) $roomIds))
            ->all();

        $this->bookingService->checkIn($booking, $roomAssignments);

        $this->auditLog->log('booking.checked_in', $booking, "Check-in đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('staff.bookings.show', $id)
            ->with('success', "Đã check-in đơn {$booking->booking_code}.");
    }

    public function checkOut(int $id): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->checkOut($booking);

        $this->auditLog->log('booking.checked_out', $booking, "Check-out đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('staff.bookings.show', $id)
            ->with('success', "Đã check-out đơn {$booking->booking_code}.");
    }

    public function complete(int $id): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->complete($booking);

        $this->auditLog->log('booking.completed', $booking, "Đánh dấu hoàn thành đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('staff.bookings.show', $id)
            ->with('success', "Đã đánh dấu hoàn thành đơn {$booking->booking_code}.");
    }

    public function updatePayment(int $id, UpdatePaymentStatusRequest $request): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->updatePaymentStatus($booking, $request->validated('status'));

        $this->auditLog->log('booking.payment_updated', $booking, "Cập nhật thanh toán đơn \"{$booking->booking_code}\" thành \"{$request->validated('status')}\".");

        return redirect()
            ->route('staff.bookings.show', $id)
            ->with('success', "Đã cập nhật trạng thái thanh toán đơn {$booking->booking_code}.");
    }
}
