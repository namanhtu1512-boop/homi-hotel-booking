<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Models\RoomChangeRequest;
use App\Services\AuditLogService;
use App\Services\RoomChangeRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class RoomChangeRequestController extends Controller
{
    public function __construct(
        private readonly RoomChangeRequestService $roomChangeRequestService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        return view('staff.room-change-requests.index', [
            'requests' => $this->roomChangeRequestService->adminList($request->only('status')),
            'filters'  => $request->only('status'),
        ]);
    }

    public function show(int $id): View
    {
        $roomChangeRequest = RoomChangeRequest::with(['booking.bookingItems.roomType', 'user', 'currentRoomType', 'requestedRoomType', 'handledByUser'])
            ->findOrFail($id);

        return view('staff.room-change-requests.show', ['roomChangeRequest' => $roomChangeRequest]);
    }

    public function approve(int $id, Request $request): RedirectResponse
    {
        $roomChangeRequest = RoomChangeRequest::findOrFail($id);

        try {
            $result = $this->roomChangeRequestService->approve($roomChangeRequest, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLog->log('room_change_request.approved', $result['booking'], "Duyệt yêu cầu đổi phòng #{$id} cho đơn {$result['booking']->booking_code}.");

        return redirect()->route('staff.room-change-requests.show', $id)->with('success', 'Đã duyệt yêu cầu đổi phòng.');
    }

    public function reject(int $id, Request $request): RedirectResponse
    {
        $roomChangeRequest = RoomChangeRequest::findOrFail($id);

        $data = $request->validate(['staff_note' => ['nullable', 'string', 'max:1000']]);

        $this->roomChangeRequestService->reject($roomChangeRequest, $request->user(), $data['staff_note'] ?? null);

        $this->auditLog->log('room_change_request.rejected', $roomChangeRequest->fresh(), "Từ chối yêu cầu đổi phòng #{$id}.");

        return redirect()->route('staff.room-change-requests.show', $id)->with('success', 'Đã từ chối yêu cầu đổi phòng.');
    }
}
