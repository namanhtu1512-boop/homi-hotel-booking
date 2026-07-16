<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupBookingRequest;
use App\Models\RoomType;
use App\Services\AuditLogService;
use App\Services\BookingService;
use App\Services\GroupBookingRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupBookingController extends Controller
{
    public function __construct(
        private readonly GroupBookingRequestService $groupBookingRequestService,
        private readonly BookingService $bookingService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.group-bookings.index', [
            'requests' => $this->groupBookingRequestService->adminList($request->only('status')),
            'filters'  => $request->only('status'),
        ]);
    }

    public function show(int $id): View
    {
        $groupRequest = GroupBookingRequest::findOrFail($id);
        $allRoomTypes = RoomType::where('status', 'active')->orderBy('name')->get();

        return view('admin.group-bookings.show', [
            'groupRequest' => $groupRequest,
            'roomTypes'    => $allRoomTypes,
            'allRoomTypes' => $allRoomTypes,
            'prefillItems' => $groupRequest->room_type_ids
                ? array_map(fn($rid) => ['room_type_id' => $rid, 'quantity' => 1, 'adults' => 2, 'children' => 0], $groupRequest->room_type_ids)
                : [['room_type_id' => '', 'quantity' => 1, 'adults' => 2, 'children' => 0]],
        ]);
    }

    public function createBooking(int $id, Request $request): RedirectResponse
    {
        $groupRequest = GroupBookingRequest::findOrFail($id);

        $data = $request->validate([
            'check_in'        => ['required', 'date', 'after_or_equal:today'],
            'check_out'       => ['required', 'date', 'after:check_in'],
            'items'           => ['required', 'array', 'min:1'],
            'items.*.room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1'],
            'items.*.adults'       => ['required', 'integer', 'min:1'],
            'items.*.children'     => ['nullable', 'integer', 'min:0'],
            'customer_name'   => ['required', 'string', 'max:100'],
            'customer_phone'  => ['required', 'string', 'max:20'],
            'customer_email'  => ['nullable', 'email', 'max:150'],
            'note'            => ['nullable', 'string', 'max:2000'],
        ]);

        $booking = $this->bookingService->createByAdmin($data);

        // Đánh dấu yêu cầu đoàn đã được xử lý
        $this->groupBookingRequestService->markContacted($groupRequest);

        $this->auditLog->log('group_booking_request.booking_created', $booking, "Tạo đơn {$booking->booking_code} từ yêu cầu đoàn #{$groupRequest->id}.");

        return redirect()
            ->route('admin.bookings.show', $booking->id)
            ->with('success', "Đã tạo đơn đặt phòng {$booking->booking_code} từ yêu cầu đoàn.");
    }

    public function markContacted(int $id): RedirectResponse
    {
        $groupBookingRequest = GroupBookingRequest::findOrFail($id);

        $this->groupBookingRequestService->markContacted($groupBookingRequest);

        $this->auditLog->log('group_booking_request.marked_contacted', $groupBookingRequest->fresh(), "Đánh dấu đã liên hệ yêu cầu đặt đoàn #{$groupBookingRequest->id}.");

        return redirect()->route('admin.group-bookings.index')->with('success', 'Đã đánh dấu đã liên hệ.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $groupBookingRequest = GroupBookingRequest::findOrFail($id);

        $this->groupBookingRequestService->delete($groupBookingRequest);

        $this->auditLog->log('group_booking_request.deleted', null, "Xóa yêu cầu đặt đoàn #{$id}.");

        return redirect()->route('admin.group-bookings.index')->with('success', 'Đã xóa yêu cầu.');
    }
}
