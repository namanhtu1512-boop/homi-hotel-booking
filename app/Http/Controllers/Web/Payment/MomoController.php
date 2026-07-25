<?php

namespace App\Http\Controllers\Web\Payment;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use App\Services\MomoPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * MoMo redirect người dùng về `return` (GET, trình duyệt) và gọi thẳng
 * server tới server ở `ipn` (POST) — cả hai đều mang cùng bộ field và
 * phải verify chữ ký trước khi tin, vì query string ở `return` khách hoàn
 * toàn có thể tự sửa tay trên URL.
 */
class MomoController extends Controller
{
    public function __construct(
        private MomoPaymentService $momoPaymentService,
        private BookingService $bookingService,
    ) {}

    public function returnUrl(Request $request): RedirectResponse
    {
        $data = $request->query();

        if (! $this->momoPaymentService->verifySignature($data)) {
            Log::warning('MoMo return: chữ ký không hợp lệ', ['data' => $data]);

            return redirect()->route('customer.bookings.index')
                ->with('error', 'Không thể xác thực kết quả thanh toán từ MoMo.');
        }

        $booking = $this->bookingService->confirmMomoPayment($data['orderId'] ?? '', $data);

        if (! $booking) {
            return redirect()->route('customer.bookings.index')
                ->with('error', 'Không tìm thấy giao dịch thanh toán tương ứng.');
        }

        $resultCode = (int) ($data['resultCode'] ?? -1);

        return redirect()->route('customer.bookings.show', $booking->id)->with(
            $resultCode === 0 ? 'success' : 'error',
            $resultCode === 0 ? 'Thanh toán MoMo thành công.' : 'Thanh toán MoMo không thành công: ' . ($data['message'] ?? 'đã hủy hoặc lỗi giao dịch.'),
        );
    }

    public function ipn(Request $request): Response
    {
        $data = $request->all();

        if (! $this->momoPaymentService->verifySignature($data)) {
            Log::warning('MoMo IPN: chữ ký không hợp lệ', ['data' => $data]);

            return response()->json(['message' => 'invalid signature'], 400);
        }

        $this->bookingService->confirmMomoPayment($data['orderId'] ?? '', $data);

        return response()->noContent();
    }
}
