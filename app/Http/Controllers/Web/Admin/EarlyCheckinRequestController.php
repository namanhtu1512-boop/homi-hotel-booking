<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\EarlyCheckinRequest;
use App\Services\AuditLogService;
use App\Services\EarlyCheckinRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class EarlyCheckinRequestController extends Controller
{
    public function __construct(
        private readonly EarlyCheckinRequestService $earlyCheckinRequestService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.early-checkin-requests.index', [
            'requests' => $this->earlyCheckinRequestService->adminList($request->only('status')),
            'filters'  => $request->only('status'),
        ]);
    }

    public function show(int $id): View
    {
        $earlyCheckinRequest = EarlyCheckinRequest::with(['booking.bookingItems.roomType', 'user', 'handledByUser'])
            ->findOrFail($id);

        return view('admin.early-checkin-requests.show', ['earlyCheckinRequest' => $earlyCheckinRequest]);
    }

    public function approve(int $id, Request $request): RedirectResponse
    {
        $earlyCheckinRequest = EarlyCheckinRequest::findOrFail($id);

        try {
            $result = $this->earlyCheckinRequestService->approve($earlyCheckinRequest, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLog->log('early_checkin_request.approved', $result['booking'], "Duyệt yêu cầu nhận phòng sớm #{$id} cho đơn {$result['booking']->booking_code}.");

        return redirect()->route('admin.early-checkin-requests.show', $id)->with('success', 'Đã duyệt yêu cầu nhận phòng sớm.');
    }

    public function reject(int $id, Request $request): RedirectResponse
    {
        $earlyCheckinRequest = EarlyCheckinRequest::findOrFail($id);

        $data = $request->validate(['staff_note' => ['nullable', 'string', 'max:1000']]);

        $this->earlyCheckinRequestService->reject($earlyCheckinRequest, $request->user(), $data['staff_note'] ?? null);

        $this->auditLog->log('early_checkin_request.rejected', $earlyCheckinRequest->fresh()->booking, "Từ chối yêu cầu nhận phòng sớm #{$id}.");

        return redirect()->route('admin.early-checkin-requests.show', $id)->with('success', 'Đã từ chối yêu cầu nhận phòng sớm.');
    }
}
