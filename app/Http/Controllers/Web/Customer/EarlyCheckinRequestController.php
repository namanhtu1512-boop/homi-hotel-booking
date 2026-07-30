<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use App\Services\EarlyCheckinRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarlyCheckinRequestController extends Controller
{
    public function __construct(
        private readonly EarlyCheckinRequestService $earlyCheckinRequestService,
        private readonly BookingService $bookingService,
    ) {}

    public function create(int $bookingId, Request $request): View
    {
        return view('customer.bookings.early-checkin', [
            'booking' => $this->bookingService->findForCustomer($bookingId, $request->user()),
        ]);
    }

    public function store(int $bookingId, Request $request): RedirectResponse
    {
        $booking = $this->bookingService->findForCustomer($bookingId, $request->user());

        $data = $request->validate([
            'requested_arrival_time' => ['required', 'date_format:H:i'],
            'reason'                 => ['nullable', 'string', 'max:1000'],
        ], [], [
            'requested_arrival_time' => 'giờ muốn nhận phòng',
            'reason'                 => 'lý do',
        ]);

        $this->earlyCheckinRequestService->create($booking, $request->user(), $data);

        return redirect()
            ->route('customer.bookings.show', $bookingId)
            ->with('success', 'Đã gửi yêu cầu nhận phòng sớm, khách sạn sẽ xem xét và phản hồi sớm.');
    }
}
