<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupBookingRequest;
use App\Services\AuditLogService;
use App\Services\GroupBookingRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupBookingController extends Controller
{
    public function __construct(
        private readonly GroupBookingRequestService $groupBookingRequestService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.group-bookings.index', [
            'requests' => $this->groupBookingRequestService->adminList($request->only('status')),
            'filters'  => $request->only('status'),
        ]);
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
