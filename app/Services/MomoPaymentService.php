<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Tích hợp cổng thanh toán MoMo thật (API v2 "captureWallet", môi trường
 * sandbox test-payment.momo.vn — không phát sinh giao dịch tiền thật, nhưng
 * đi đúng luồng redirect + chữ ký HMAC như production).
 *
 * Tài liệu: https://developers.momo.vn/v3/vi/docs/payment/api/wallet/onetime
 */
class MomoPaymentService
{
    private array $config;

    public function __construct()
    {
        $this->config = config('services.momo');
    }

    /**
     * Gọi API tạo giao dịch MoMo, trả về payUrl để redirect khách sang.
     *
     * @param int $amountVnd Số tiền VND (số nguyên, MoMo không nhận số thập phân)
     * @param array<string, mixed> $extraData Dữ liệu tự định nghĩa, sẽ được MoMo trả nguyên vẹn lại ở bước return/IPN
     * @return array{orderId: string, requestId: string, payUrl: string}
     */
    public function createPaymentUrl(int $amountVnd, string $orderInfo, array $extraData): array
    {
        $partnerCode = $this->config['partner_code'];
        $accessKey   = $this->config['access_key'];
        $secretKey   = $this->config['secret_key'];

        $orderId     = 'HOMI' . now()->format('YmdHis') . Str::upper(Str::random(6));
        $requestId   = (string) Str::uuid();
        $redirectUrl = route('payment.momo.return');
        $ipnUrl      = route('payment.momo.ipn');
        $requestType = 'captureWallet';
        $extraDataEncoded = base64_encode(json_encode($extraData));

        $rawSignature = "accessKey={$accessKey}"
            . "&amount={$amountVnd}"
            . "&extraData={$extraDataEncoded}"
            . "&ipnUrl={$ipnUrl}"
            . "&orderId={$orderId}"
            . "&orderInfo={$orderInfo}"
            . "&partnerCode={$partnerCode}"
            . "&redirectUrl={$redirectUrl}"
            . "&requestId={$requestId}"
            . "&requestType={$requestType}";

        $signature = hash_hmac('sha256', $rawSignature, $secretKey);

        $response = Http::asJson()->post($this->config['endpoint'], [
            'partnerCode' => $partnerCode,
            'accessKey'   => $accessKey,
            'requestId'   => $requestId,
            'amount'      => $amountVnd,
            'orderId'     => $orderId,
            'orderInfo'   => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl'      => $ipnUrl,
            'extraData'   => $extraDataEncoded,
            'requestType' => $requestType,
            'signature'   => $signature,
            'lang'        => 'vi',
        ]);

        $body = $response->json();

        if (! $response->successful() || (int) ($body['resultCode'] ?? -1) !== 0) {
            Log::warning('MoMo createPaymentUrl thất bại', ['orderId' => $orderId, 'response' => $body]);

            throw new RuntimeException($body['message'] ?? 'Không thể khởi tạo thanh toán MoMo, vui lòng thử lại.');
        }

        return [
            'orderId'   => $orderId,
            'requestId' => $requestId,
            'payUrl'    => $body['payUrl'],
            'raw'       => $body,
        ];
    }

    /**
     * Xác thực chữ ký MoMo gửi về ở bước return (redirect trình duyệt) hoặc
     * IPN (server gọi thẳng) — bắt buộc phải kiểm tra trước khi tin bất kỳ
     * trường nào trong $data, tránh khách tự chế query string giả để đánh
     * lừa hệ thống là đã thanh toán.
     */
    public function verifySignature(array $data): bool
    {
        $accessKey = $this->config['access_key'];
        $secretKey = $this->config['secret_key'];

        $rawSignature = "accessKey={$accessKey}"
            . "&amount=" . ($data['amount'] ?? '')
            . "&extraData=" . ($data['extraData'] ?? '')
            . "&message=" . ($data['message'] ?? '')
            . "&orderId=" . ($data['orderId'] ?? '')
            . "&orderInfo=" . ($data['orderInfo'] ?? '')
            . "&orderType=" . ($data['orderType'] ?? '')
            . "&partnerCode=" . ($data['partnerCode'] ?? '')
            . "&payType=" . ($data['payType'] ?? '')
            . "&requestId=" . ($data['requestId'] ?? '')
            . "&responseTime=" . ($data['responseTime'] ?? '')
            . "&resultCode=" . ($data['resultCode'] ?? '')
            . "&transId=" . ($data['transId'] ?? '');

        $expected = hash_hmac('sha256', $rawSignature, $secretKey);

        return hash_equals($expected, (string) ($data['signature'] ?? ''));
    }

    public function decodeExtraData(?string $extraData): array
    {
        if (! $extraData) {
            return [];
        }

        return json_decode(base64_decode($extraData), true) ?? [];
    }
}
