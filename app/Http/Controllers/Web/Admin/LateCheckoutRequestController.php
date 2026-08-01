<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\LateCheckoutRequest;
use App\Services\AuditLogService;
use App\Services\LateCheckoutRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class LateCheckoutRequestController extends Controller
{
    public function __construct(
        private readonly LateCheckoutRequestService $lateCheckoutRequestService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.late-checkout-requests.index', [
            'requests' => $this->lateCheckoutRequestService->adminList($request->only('status')),
            'filters'  => $request->only('status'),
        ]);
    }

    public function show(int $id): View
    {
        $lateCheckoutRequest = LateCheckoutRequest::with(['booking.bookingItems.roomType', 'user', 'handledByUser'])
            ->findOrFail($id);

        return view('admin.late-checkout-requests.show', ['lateCheckoutRequest' => $lateCheckoutRequest]);
    }

    public function approve(int $id, Request $request): RedirectResponse
    {
        $lateCheckoutRequest = LateCheckoutRequest::findOrFail($id);

        try {
            $result = $this->lateCheckoutRequestService->approve($lateCheckoutRequest, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLog->log('late_checkout_request.approved', $result['booking'], "Duyệt yêu cầu trả phòng muộn #{$id} cho đơn {$result['booking']->booking_code}.");

        return redirect()->route('admin.late-checkout-requests.show', $id)->with('success', 'Đã duyệt yêu cầu trả phòng muộn.');
    }

    public function reject(int $id, Request $request): RedirectResponse
    {
        $lateCheckoutRequest = LateCheckoutRequest::findOrFail($id);

        $data = $request->validate(['staff_note' => ['nullable', 'string', 'max:1000']]);

        $this->lateCheckoutRequestService->reject($lateCheckoutRequest, $request->user(), $data['staff_note'] ?? null);

        $this->auditLog->log('late_checkout_request.rejected', $lateCheckoutRequest->fresh(), "Từ chối yêu cầu trả phòng muộn #{$id}.");

        return redirect()->route('admin.late-checkout-requests.show', $id)->with('success', 'Đã từ chối yêu cầu trả phòng muộn.');
    }
}
