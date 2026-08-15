<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Models\ExtraBedRequest;
use App\Services\AuditLogService;
use App\Services\ExtraBedInventoryService;
use App\Services\ExtraBedRequestService;
use App\Services\RoomTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExtraBedRequestController extends Controller
{
    public function __construct(
        private readonly ExtraBedRequestService $extraBedRequestService,
        private readonly ExtraBedInventoryService $extraBedInventoryService,
        private readonly AuditLogService $auditLog,
        private readonly RoomTypeService $roomTypeService,
    ) {}

    public function index(Request $request): View
    {
        $date = $request->input('date') ?: now()->toDateString();

        return view('staff.extra-bed-requests.index', [
            'requests' => $this->extraBedRequestService->adminList($request->only('status')),
            'filters'  => $request->only('status'),
            'usage'    => $this->extraBedInventoryService->usageOnDate($date),
        ]);
    }

    public function show(int $id): View
    {
        $extraBedRequest = ExtraBedRequest::with(['booking.bookingItems.roomType', 'bookingItem.roomType', 'handledByUser'])
            ->findOrFail($id);

        return view('staff.extra-bed-requests.show', [
            'extraBedRequest' => $extraBedRequest,
            'roomTypes'       => $this->roomTypeService->list(),
        ]);
    }

    public function resolve(int $id, Request $request): RedirectResponse
    {
        $extraBedRequest = ExtraBedRequest::findOrFail($id);

        $data = $request->validate([
            'choice'       => ['required', 'in:upgrade_room,add_room,drop_extra_bed,waitlist,staff_cancel'],
            'room_type_id' => ['nullable', 'integer', 'exists:room_types,id'],
        ], [], ['choice' => 'phương án', 'room_type_id' => 'loại phòng mới']);

        try {
            $resolved = $this->extraBedRequestService->resolve($extraBedRequest, $data['choice'], $request->user(), $data['room_type_id'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLog->log('extra_bed_request.resolved', $resolved->booking, "Xử lý yêu cầu giường phụ #{$id} ({$data['choice']}) cho đơn {$resolved->booking->booking_code}.");

        return redirect()->route('staff.extra-bed-requests.show', $id)->with('success', 'Đã xử lý yêu cầu giường phụ.');
    }
}
