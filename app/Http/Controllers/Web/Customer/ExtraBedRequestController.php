<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use App\Services\ExtraBedRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExtraBedRequestController extends Controller
{
    public function __construct(
        private readonly ExtraBedRequestService $extraBedRequestService,
        private readonly BookingService $bookingService,
    ) {}

    public function resolve(int $bookingId, Request $request): RedirectResponse
    {
        // findForCustomer() tự authorize (Gate 'view' Booking) — đảm bảo
        // khách chỉ resolve được yêu cầu giường phụ của chính đơn của mình.
        $booking = $this->bookingService->findForCustomer($bookingId, $request->user());

        $extraBedRequest = $booking->pendingExtraBedRequest();
        if (! $extraBedRequest) {
            return back()->withErrors(['choice' => ['Đơn này không có yêu cầu giường phụ nào đang chờ xử lý.']]);
        }

        // Không có staff_cancel — khách không tự hủy đơn qua lối này (đã có
        // nút "Hủy đơn" riêng ở trang chi tiết booking).
        $data = $request->validate([
            'choice'       => ['required', 'in:upgrade_room,add_room,drop_extra_bed,waitlist'],
            'room_type_id' => ['nullable', 'integer', 'exists:room_types,id'],
        ], [], ['choice' => 'phương án', 'room_type_id' => 'loại phòng mới']);

        try {
            $this->extraBedRequestService->resolve($extraBedRequest, $data['choice'], null, $data['room_type_id'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('customer.bookings.show', $bookingId)->with('success', 'Đã ghi nhận lựa chọn của bạn cho yêu cầu giường phụ.');
    }
}
