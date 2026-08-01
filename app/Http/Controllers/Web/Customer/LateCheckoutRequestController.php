<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use App\Services\LateCheckoutRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LateCheckoutRequestController extends Controller
{
    public function __construct(
        private readonly LateCheckoutRequestService $lateCheckoutRequestService,
        private readonly BookingService $bookingService,
    ) {}

    public function create(int $bookingId, Request $request): View
    {
        return view('customer.bookings.late-checkout', [
            'booking' => $this->bookingService->findForCustomer($bookingId, $request->user()),
        ]);
    }

    public function store(int $bookingId, Request $request): RedirectResponse
    {
        $booking = $this->bookingService->findForCustomer($bookingId, $request->user());

        $data = $request->validate([
            'requested_checkout_time' => ['required', 'date_format:H:i'],
            'reason'                  => ['nullable', 'string', 'max:1000'],
        ], [], [
            'requested_checkout_time' => 'giờ muốn trả phòng',
            'reason'                  => 'lý do',
        ]);

        $this->lateCheckoutRequestService->create($booking, $request->user(), $data);

        return redirect()
            ->route('customer.bookings.show', $bookingId)
            ->with('success', 'Đã gửi yêu cầu trả phòng muộn, khách sạn sẽ xem xét và phản hồi sớm.');
    }
}
