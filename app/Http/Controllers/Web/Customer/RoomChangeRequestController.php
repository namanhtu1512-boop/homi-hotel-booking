<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use App\Services\RoomChangeRequestService;
use App\Services\RoomTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomChangeRequestController extends Controller
{
    public function __construct(
        private readonly RoomChangeRequestService $roomChangeRequestService,
        private readonly BookingService $bookingService,
        private readonly RoomTypeService $roomTypeService,
    ) {}

    public function create(int $bookingId, Request $request): View
    {
        return view('customer.bookings.room-change', [
            'booking'   => $this->bookingService->findForCustomer($bookingId, $request->user()),
            'roomTypes' => $this->roomTypeService->list(),
        ]);
    }

    public function store(int $bookingId, Request $request): RedirectResponse
    {
        $booking = $this->bookingService->findForCustomer($bookingId, $request->user());

        $data = $request->validate([
            'requested_room_type_id' => ['nullable', 'integer', 'exists:room_types,id'],
            'requested_check_in'     => ['nullable', 'date', 'after_or_equal:' . now('Asia/Ho_Chi_Minh')->toDateString(), 'required_with:requested_check_out'],
            'requested_check_out'    => ['nullable', 'date', 'after:requested_check_in', 'required_with:requested_check_in'],
            'reason'                 => ['nullable', 'string', 'max:1000'],
        ], [], [
            'requested_room_type_id' => 'loại phòng mới',
            'requested_check_in'     => 'ngày nhận phòng mới',
            'requested_check_out'    => 'ngày trả phòng mới',
            'reason'                 => 'lý do',
        ]);

        $this->roomChangeRequestService->create($booking, $request->user(), $data);

        return redirect()
            ->route('customer.bookings.show', $bookingId)
            ->with('success', 'Đã gửi yêu cầu đổi phòng, khách sạn sẽ xem xét và phản hồi sớm.');
    }
}
