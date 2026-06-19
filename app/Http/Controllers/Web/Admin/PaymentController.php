<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $query = Payment::with('booking')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('method')) {
            $query->where('method', $request->input('method'));
        }

        $payments = $query->paginate(15)->withQueryString();

        $summary = [
            'paid'     => Payment::where('status', 'paid')->sum('amount'),
            'unpaid'   => Payment::whereIn('status', ['unpaid', 'pending'])->sum('amount'),
            'refunded' => Payment::where('status', 'refunded')->sum('amount'),
        ];

        return view('admin.payments.index', [
            'payments' => $payments,
            'summary'  => $summary,
            'status'   => $request->input('status', ''),
            'method'   => $request->input('method', ''),
        ]);
    }

    public function confirmCash(Payment $payment): RedirectResponse
    {
        if ($payment->method !== PaymentMethod::PAY_AT_HOTEL || $payment->status->isPaid()) {
            abort(422, 'Không thể xác nhận thanh toán này.');
        }

        $payment->update(['status' => PaymentStatus::PAID, 'paid_at' => now()]);

        $bookingCode = $payment->booking?->booking_code ?? $payment->id;
        $this->auditLog->log('payment.confirmed_cash', $payment, "Xác nhận thu tiền mặt cho đặt phòng \"{$bookingCode}\".");

        return redirect()
            ->back()
            ->with('success', "Đã xác nhận thu tiền mặt cho đặt phòng \"{$bookingCode}\".");
    }
}
