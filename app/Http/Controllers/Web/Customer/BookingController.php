<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Services\BookingService;
use App\Services\RoomTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly RoomTypeService $roomTypeService,
    ) {}

    public function create(Request $request): View
    {
        $roomTypeId = $request->query('room_type_id');
        $roomType = $roomTypeId ? $this->roomTypeService->findActive((int) $roomTypeId) : null;

        return view('customer.booking.create', [
            'roomType'  => $roomType,
            'roomTypes' => $roomType ? collect() : $this->roomTypeService->list(),
            'checkIn'   => $request->query('check_in'),
            'checkOut'  => $request->query('check_out'),
            'quantity'  => max(1, (int) $request->query('quantity', 1)),
        ]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $booking = $this->bookingService->create($request->user(), $request->validated());

        return redirect()
            ->route('customer.bookings.show', $booking->id)
            ->with('success', "Đặt phòng thành công! Mã đơn: {$booking->booking_code}.");
    }

    public function index(Request $request): View
    {
        $bookings = $this->bookingService->myBookings(
            $request->user(),
            $request->only('status'),
        )->appends($request->only('status'));

        return view('customer.bookings.index', ['bookings' => $bookings]);
    }

    public function show(int $id, Request $request): View
    {
        return view('customer.bookings.show', [
            'booking' => $this->bookingService->findForCustomer($id, $request->user()),
        ]);
    }

    public function cancel(int $id, Request $request): RedirectResponse
    {
        $this->bookingService->cancelByCustomer($id, $request->user());

        return redirect()
            ->route('customer.bookings.show', $id)
            ->with('success', 'Đã hủy đơn đặt phòng.');
    }
}
