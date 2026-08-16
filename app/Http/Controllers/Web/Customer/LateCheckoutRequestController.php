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
            'hours_late' => ['required', 'integer', 'min:1', 'max:6'],
            'reason'     => ['nullable', 'string', 'max:1000'],
        ], [], [
            'hours_late' => 'số giờ muốn trả phòng trễ',
            'reason'     => 'lý do',
        ]);

        $lateCheckoutRequest = $this->lateCheckoutRequestService->create($booking, $request->user(), $data);

        $message = $lateCheckoutRequest->status === 'approved'
            ? 'Yêu cầu trả phòng muộn đã được TỰ ĐỘNG duyệt (trễ không quá 1 giờ). Phụ phí đã ghi vào hóa đơn phát sinh, thanh toán khi trả phòng.'
            : 'Đã gửi yêu cầu trả phòng muộn, khách sạn sẽ xem xét và phản hồi sớm.';

        return redirect()
            ->route('customer.bookings.show', $bookingId)
            ->with('success', $message);
    }
}
