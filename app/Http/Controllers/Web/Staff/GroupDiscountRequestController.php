<?php

namespace App\Http\Controllers\Web\Staff;

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

    /**
     * Lịch sử đề xuất ưu đãi đoàn CỦA CHÍNH nhân viên đang đăng nhập — chỉ
     * xem, không duyệt/từ chối được (chỉ admin mới có quyền đó, xem
     * Admin\GroupDiscountRequestController).
     */
    public function index(Request $request): View
    {
        return view('staff.group-discount-requests.index', [
            'requests' => $this->requestService->myList($request->user()->id, $request->only('status')),
            'filters'  => $request->only('status'),
        ]);
    }

    public function show(int $id): View
    {
        $groupDiscountRequest = GroupDiscountRequest::with(['booking.bookingItems.roomType', 'booking.payment', 'user', 'handledByUser', 'policy'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return view('staff.group-discount-requests.show', ['groupDiscountRequest' => $groupDiscountRequest]);
    }

    /**
     * Nhân viên đề xuất/áp dụng giảm giá thêm cho 1 đơn — áp ngay nếu trong
     * trần cấu hình, ngược lại tạo yêu cầu chờ admin duyệt (xem
     * GroupDiscountRequestService::proposeExtra()).
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
            return redirect()->route('staff.bookings.show', $bookingId)->with('error', collect($e->errors())->flatten()->first());
        }

        if ($result['applied']) {
            $this->auditLog->log('group_discount_request.applied', $booking, "Áp dụng giảm thêm {$data['percent']}% cho đơn {$booking->booking_code}.");

            return redirect()->route('staff.bookings.show', $bookingId)->with('success', "Đã áp dụng giảm {$data['percent']}% cho đơn {$booking->booking_code}.");
        }

        $this->auditLog->log('group_discount_request.submitted', $booking, "Gửi đề xuất giảm thêm {$data['percent']}% cho đơn {$booking->booking_code} — vượt trần, chờ admin duyệt.");

        return redirect()->route('staff.bookings.show', $bookingId)->with('success', "Đã gửi đề xuất giảm {$data['percent']}% cho đơn {$booking->booking_code} — vượt trần cho phép, đang chờ admin duyệt.");
    }
}
