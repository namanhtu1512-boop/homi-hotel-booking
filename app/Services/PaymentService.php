<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    private const VALID_TRANSITIONS = [
        'unpaid'   => ['paid'],
        'pending'  => ['paid'],
        'paid'     => ['refunded'],
        'refunded' => [],
    ];

    public function updateStatus(Booking $booking, string $newStatus): Payment
    {
        $payment = $booking->payment;

        if (! $payment) {
            abort(404, 'Không tìm thấy thông tin thanh toán cho đơn này.');
        }

        $allowed = self::VALID_TRANSITIONS[$payment->status] ?? [];

        if (! in_array($newStatus, $allowed)) {
            throw ValidationException::withMessages([
                'payment_status' => [
                    "Không thể chuyển từ trạng thái '{$payment->status}' sang '{$newStatus}'.",
                ],
            ]);
        }

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'paid') {
            $updateData['paid_at'] = now();
        }

        if ($newStatus === 'refunded') {
            $updateData['refunded_at'] = now();
        }

        $payment->update($updateData);

        return $payment->fresh();
    }
}
