<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Tích hợp cổng thanh toán VNPay (môi trường sandbox). Chữ ký request/response
 * đều dùng HMAC-SHA512 theo tài liệu tích hợp chính thức của VNPay:
 * https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html
 */
class VNPayService
{
    private string $tmnCode;
    private string $hashSecret;
    private string $payUrl;
    private string $apiUrl;

    public function __construct()
    {
        $this->tmnCode    = (string) config('services.vnpay.tmn_code');
        $this->hashSecret = (string) config('services.vnpay.hash_secret');
        $this->payUrl     = (string) config('services.vnpay.pay_url');
        $this->apiUrl     = (string) config('services.vnpay.api_url');
    }

    /**
     * Sinh mã tham chiếu giao dịch (vnp_TxnRef) — phải là duy nhất cho mỗi
     * lần thử thanh toán (không phải mỗi đơn), vì một đơn có thể thanh toán
     * lại nhiều lần nếu lần trước thất bại/hủy giữa chừng.
     */
    public function generateTxnRef(string $bookingCode): string
    {
        return Str::upper(str_replace('-', '', $bookingCode)) . now()->format('His') . random_int(100, 999);
    }

    /**
     * Tạo URL redirect sang cổng thanh toán VNPay.
     */
    public function buildPaymentUrl(string $txnRef, float $amount, string $orderInfo, string $ipAddress, string $returnUrl): string
    {
        $params = [
            'vnp_Version'    => '2.1.0',
            'vnp_Command'    => 'pay',
            'vnp_TmnCode'    => $this->tmnCode,
            'vnp_Amount'     => (int) round($amount * 100),
            'vnp_CurrCode'   => 'VND',
            'vnp_TxnRef'     => $txnRef,
            'vnp_OrderInfo'  => $orderInfo,
            'vnp_OrderType'  => 'other',
            'vnp_Locale'     => 'vn',
            'vnp_ReturnUrl'  => $returnUrl,
            'vnp_IpAddr'     => $ipAddress,
            // VNPay luôn hiểu vnp_CreateDate/vnp_ExpireDate theo giờ Việt Nam
            // (UTC+7) bất kể server chạy múi giờ nào — app này chạy UTC
            // (config/app.php), nên phải quy đổi tường minh, nếu không giao
            // dịch sẽ bị coi là hết hạn ngay khi vừa tạo (lệch 7 tiếng).
            'vnp_CreateDate' => now('Asia/Ho_Chi_Minh')->format('YmdHis'),
            'vnp_ExpireDate' => now('Asia/Ho_Chi_Minh')->addMinutes(15)->format('YmdHis'),
        ];

        ksort($params);

        $query = http_build_query($params, '', '&', PHP_QUERY_RFC1738);
        $hash  = hash_hmac('sha512', $query, $this->hashSecret);

        return $this->payUrl . '?' . $query . '&vnp_SecureHash=' . $hash;
    }

    /**
     * Xác thực chữ ký của dữ liệu VNPay gửi về (return URL hoặc IPN) — so
     * sánh bằng hash_equals để tránh timing attack.
     */
    public function verifySecureHash(array $data): bool
    {
        $receivedHash = $data['vnp_SecureHash'] ?? null;

        if (! $receivedHash) {
            return false;
        }

        unset($data['vnp_SecureHash'], $data['vnp_SecureHashType']);

        ksort($data);
        $query = http_build_query($data, '', '&', PHP_QUERY_RFC1738);
        $expectedHash = hash_hmac('sha512', $query, $this->hashSecret);

        return hash_equals($expectedHash, $receivedHash);
    }

    public function isSuccessResponse(array $data): bool
    {
        return ($data['vnp_ResponseCode'] ?? null) === '00'
            && ($data['vnp_TransactionStatus'] ?? null) === '00';
    }

    /**
     * Gọi API hoàn tiền (refund toàn phần) của VNPay cho một giao dịch đã
     * thanh toán thành công trước đó. Trả về mảng response thô của VNPay —
     * gọi nơi khác kiểm tra vnp_ResponseCode === '00' để biết hoàn tiền có
     * thành công hay không.
     *
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function refund(
        string $txnRef,
        float $amount,
        string $gatewayTransactionNo,
        string $gatewayPaidAt,
        string $orderInfo,
        string $createBy,
        string $ipAddress,
    ): array {
        $requestId = (string) Str::uuid();
        // Giờ Việt Nam, cùng quy ước với buildPaymentUrl() — app chạy UTC
        // (config/app.php) nhưng VNPay luôn hiểu các mốc thời gian là giờ VN.
        $createDate = now('Asia/Ho_Chi_Minh')->format('YmdHis');

        $data = [
            $requestId,
            '2.1.0',
            'refund',
            $this->tmnCode,
            '02', // hoàn toàn phần
            $txnRef,
            (int) round($amount * 100),
            $gatewayTransactionNo,
            $gatewayPaidAt,
            $createBy,
            $createDate,
            $ipAddress,
            $orderInfo,
        ];

        $hash = hash_hmac('sha512', implode('|', $data), $this->hashSecret);

        $payload = [
            'vnp_RequestId'         => $requestId,
            'vnp_Version'           => '2.1.0',
            'vnp_Command'           => 'refund',
            'vnp_TmnCode'           => $this->tmnCode,
            'vnp_TransactionType'   => '02',
            'vnp_TxnRef'            => $txnRef,
            'vnp_Amount'            => (int) round($amount * 100),
            'vnp_TransactionNo'     => $gatewayTransactionNo,
            'vnp_TransactionDate'   => $gatewayPaidAt,
            'vnp_CreateBy'          => $createBy,
            'vnp_CreateDate'        => $createDate,
            'vnp_IpAddr'            => $ipAddress,
            'vnp_OrderInfo'         => $orderInfo,
            'vnp_SecureHash'        => $hash,
        ];

        // Gọi đồng bộ ngay trong request hủy đơn (xem BookingService::attemptRefund())
        // — bắt buộc có timeout để 1 lần VNPay chậm/không phản hồi không
        // treo cả request hủy đơn của admin/khách vô thời hạn.
        $response = Http::asJson()->timeout(10)->connectTimeout(5)->post($this->apiUrl, $payload);

        return $response->json() ?? [];
    }
}
