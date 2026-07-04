<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\UpdatePaymentStatusRequest;
use App\Services\AuditLogService;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'booking_code', 'customer_name']);

        $payments = $this->bookingService->adminPaymentsList($filters, 20)->appends($filters);

        return view('staff.payments.index', [
            'payments' => $payments,
            'filters'  => $filters,
        ]);
    }

    public function updateStatus(int $id, UpdatePaymentStatusRequest $request): RedirectResponse
    {
        $payment = $this->bookingService->findPaymentForAdmin($id);
        $booking = $this->bookingService->updatePaymentStatus($payment->booking, $request->validated('status'));

        $this->auditLog->log('booking.payment_updated', $booking, "Cập nhật thanh toán đơn \"{$booking->booking_code}\" thành \"{$request->validated('status')}\".");

        return redirect()
            ->route('staff.payments.index')
            ->with('success', "Đã cập nhật trạng thái thanh toán đơn {$booking->booking_code}.");
    }
}
