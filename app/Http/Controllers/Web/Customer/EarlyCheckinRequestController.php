<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Models\HotelInfo;
use App\Services\BookingService;
use App\Services\EarlyCheckinRequestService;
use Carbon\Carbon;
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
        $booking = $this->bookingService->findForCustomer($bookingId, $request->user());

        abort_unless($booking->canRequestEarlyCheckin(), 403);

        return view('customer.bookings.early-checkin', [
            'booking' => $booking,
        ]);
    }

    public function store(int $bookingId, Request $request): RedirectResponse
    {
        $booking = $this->bookingService->findForCustomer($bookingId, $request->user());

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'reason' => 'lý do',
        ]);

        $standardTime = substr(HotelInfo::instance()->check_in_time ?? '14:00:00', 0, 5);
        $data['requested_arrival_time'] = Carbon::createFromFormat('H:i', $standardTime)
            ->subHours(EarlyCheckinRequestService::AUTO_APPROVE_MAX_HOURS)
            ->format('H:i');

        $this->earlyCheckinRequestService->create($booking, $request->user(), $data);

        return redirect()
            ->route('customer.bookings.show', $bookingId)
            ->with('success', 'Đã gửi yêu cầu nhận phòng sớm, khách sạn sẽ xem xét và phản hồi sớm.');
    }
}
