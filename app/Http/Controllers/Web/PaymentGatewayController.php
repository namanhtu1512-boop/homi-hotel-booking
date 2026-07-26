<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Endpoint VNPay gọi về sau khi khách thanh toán — cả return URL (trình
 * duyệt khách redirect về) lẫn IPN (server VNPay gọi thẳng, đáng tin cậy
 * hơn vì không đi qua trình duyệt khách) đều dùng chung
 * BookingService::confirmVnpayReturn(), idempotent nên gọi 2 lần cho cùng 1
 * giao dịch vẫn an toàn.
 */
class PaymentGatewayController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function vnpayReturn(Request $request): RedirectResponse
    {
        $result = $this->bookingService->confirmVnpayReturn($request->query());

        $booking = $result['booking'];

        if (! $booking) {
            return redirect()->route('customer.bookings.index')->with('error', $result['message']);
        }

        return redirect()
            ->route('customer.bookings.show', $booking->id)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function vnpayIpn(Request $request): JsonResponse
    {
        $result = $this->bookingService->confirmVnpayReturn($request->all());

        // Mã lỗi theo đúng quy ước VNPay yêu cầu ở endpoint IPN — '00' nghĩa
        // là "đã nhận và xử lý xong", bất kể giao dịch thành công hay thất
        // bại; chỉ dùng mã lỗi khác khi merchant KHÔNG xử lý được thông báo
        // (không tìm thấy đơn, sai chữ ký...).
        $rspCode = match ($result['code']) {
            'not_found'         => '01',
            'invalid_signature' => '97',
            'amount_mismatch'   => '04',
            default             => '00',
        };

        return response()->json(['RspCode' => $rspCode, 'Message' => $result['message']]);
    }
}
