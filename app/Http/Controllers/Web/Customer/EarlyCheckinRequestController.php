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
            'booking' => $booking->load('bookingItems.roomType'),
        ]);
    }

    public function store(int $bookingId, Request $request): RedirectResponse
    {
        $booking = $this->bookingService->findForCustomer($bookingId, $request->user());

        $data = $request->validate([
            'reason'              => ['nullable', 'string', 'max:1000'],
            'room_selections'     => ['required', 'array'],
            'room_selections.*'   => ['integer', 'min:0'],
        ], [], [
            'reason'           => 'lý do',
            'room_selections'  => 'phòng muốn nhận sớm',
        ]);

        $standardTime = substr(HotelInfo::instance()->check_in_time ?? '14:00:00', 0, 5);
        $earlyTime = Carbon::createFromFormat('H:i', $standardTime)
            ->subHours(EarlyCheckinRequestService::AUTO_APPROVE_MAX_HOURS)
            ->format('H:i');
        $data['requested_arrival_time'] = $earlyTime;

        $this->earlyCheckinRequestService->create($booking, $request->user(), $data);

        // Form khách chỉ cho phép yêu cầu đúng AUTO_APPROVE_MAX_HOURS giờ sớm
        // (xem $data['requested_arrival_time'] ở trên) nên luôn được tự động
        // duyệt ngay — thông báo phải phản ánh đúng, không được nói kiểu
        // "khách sạn sẽ xem xét" như đang chờ duyệt.
        return redirect()
            ->route('customer.bookings.show', $bookingId)
            ->with('success', "Yêu cầu nhận phòng sớm đã được duyệt tự động! Bạn có thể nhận phòng lúc {$earlyTime} như đã chọn.");
    }
}
