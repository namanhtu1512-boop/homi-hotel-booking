<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\GroupDiscountRequest;
use App\Services\AuditLogService;
use App\Services\GroupDiscountRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupDiscountRequestController extends Controller
{
    public function __construct(
        private readonly GroupDiscountRequestService $requestService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.group-discount-requests.index', [
            'requests' => $this->requestService->adminList($request->only('status', 'type', 'user_id')),
            'filters'  => $request->only('status', 'type', 'user_id'),
        ]);
    }

    public function show(int $id): View
    {
        $groupDiscountRequest = GroupDiscountRequest::with(['booking.bookingItems.roomType', 'booking.payment', 'user', 'handledByUser', 'policy'])
            ->findOrFail($id);

        return view('admin.group-discount-requests.show', ['groupDiscountRequest' => $groupDiscountRequest]);
    }

    public function approve(int $id, Request $request): RedirectResponse
    {
        $groupDiscountRequest = GroupDiscountRequest::findOrFail($id);

        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:1000']]);

        try {
            $result = $this->requestService->approve($groupDiscountRequest, $request->user(), null, $data['admin_note'] ?? null);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.group-discount-requests.show', $id)->with('error', collect($e->errors())->flatten()->first());
        }

        $this->auditLog->log('group_discount_request.approved', $result['booking'], "Duyệt đề xuất giảm giá đoàn #{$groupDiscountRequest->id} cho đơn {$result['booking']->booking_code} ({$result['request']->approved_percent}%).");

        return redirect()->route('admin.group-discount-requests.show', $id)->with('success', 'Đã duyệt đề xuất giảm giá.');
    }

    public function reject(int $id, Request $request): RedirectResponse
    {
        $groupDiscountRequest = GroupDiscountRequest::findOrFail($id);

        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:1000']]);

        $this->requestService->reject($groupDiscountRequest, $request->user(), $data['admin_note'] ?? null);

        $this->auditLog->log('group_discount_request.rejected', $groupDiscountRequest->booking, "Từ chối đề xuất giảm giá đoàn #{$groupDiscountRequest->id} cho đơn {$groupDiscountRequest->booking->booking_code}.");

        return redirect()->route('admin.group-discount-requests.show', $id)->with('success', 'Đã từ chối đề xuất giảm giá.');
    }

    public function adjust(int $id, Request $request): RedirectResponse
    {
        $groupDiscountRequest = GroupDiscountRequest::findOrFail($id);

        $data = $request->validate([
            'percent'    => ['required', 'numeric', 'min:0.01', 'max:100'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $result = $this->requestService->approve($groupDiscountRequest, $request->user(), (float) $data['percent'], $data['admin_note'] ?? null);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.group-discount-requests.show', $id)->with('error', collect($e->errors())->flatten()->first());
        }

        $this->auditLog->log('group_discount_request.adjusted', $result['booking'], "Điều chỉnh đề xuất giảm giá đoàn #{$groupDiscountRequest->id} cho đơn {$result['booking']->booking_code}: {$groupDiscountRequest->requested_percent}% → {$result['request']->approved_percent}%.");

        return redirect()->route('admin.group-discount-requests.show', $id)->with('success', 'Đã điều chỉnh và áp dụng đề xuất giảm giá.');
    }

    /**
     * Admin áp dụng giảm giá thêm trực tiếp lên 1 đơn — không bị trần cấu
     * hình cho nhân viên (admin có toàn quyền, không cần tự duyệt cho mình).
     */
    public function store(int $bookingId, Request $request): RedirectResponse
    {
        $booking = Booking::findOrFail($bookingId);

        $data = $request->validate([
            'percent' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'reason'  => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $result = $this->requestService->proposeExtra($booking, $request->user(), (float) $data['percent'], $data['reason'] ?? null);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.bookings.show', $bookingId)->with('error', collect($e->errors())->flatten()->first());
        }

        $this->auditLog->log('group_discount_request.applied', $booking, "Áp dụng giảm thêm {$data['percent']}% cho đơn {$booking->booking_code}.");

        return redirect()->route('admin.bookings.show', $bookingId)->with('success', "Đã áp dụng giảm {$data['percent']}% cho đơn {$booking->booking_code}.");
    }
}
