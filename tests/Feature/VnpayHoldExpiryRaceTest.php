<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundRequestStatus;
use App\Models\Booking;
use App\Models\RefundRequest;
use App\Models\User;
use App\Notifications\OrphanedPaymentNeedsRefund;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Race condition: booking hết hạn hold (deposit_expires_at) trong khi khách
 * vẫn đang trong phiên thanh toán VNPay còn hạn — hệ thống KHÔNG được hủy
 * đơn+nhả phòng ngay lập tức, và KHÔNG được lặng lẽ bỏ qua một callback báo
 * thanh toán thành công (xem BookingService::processBookingExpiry()/
 * confirmVnpayReturn()).
 */
class VnpayHoldExpiryRaceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BookingService
    {
        return app(BookingService::class);
    }

    private function makeBooking(BookingStatus $status, array $overrides = []): Booking
    {
        $customer = User::factory()->create();

        return Booking::factory()->create(array_merge([
            'user_id'      => $customer->id,
            'status'       => $status,
            'total_amount' => 1000000,
        ], $overrides));
    }

    private function makePendingVnpayPayment(Booking $booking, string $txnRef, float $amount): void
    {
        $booking->payment()->create([
            'method'                 => PaymentMethod::ONLINE_VNPAY,
            'amount'                 => $amount,
            'amount_collected'       => 0,
            'status'                 => PaymentStatus::PENDING,
            'transaction_code'       => $txnRef,
            'pending_gateway_amount' => $amount,
        ]);
    }

    /**
     * Ký query giả lập VNPay gọi về — CÙNG thuật toán với
     * VNPayService::verifySecureHash() (ksort + http_build_query RFC1738 +
     * hash_hmac sha512), dùng đúng hash_secret đang cấu hình cho môi trường
     * test thay vì hardcode 1 secret khác.
     */
    private function signedSuccessQuery(string $txnRef, float $amount): array
    {
        $data = [
            'vnp_Amount'            => (int) round($amount * 100),
            'vnp_ResponseCode'      => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TransactionNo'     => '14000123',
            'vnp_TxnRef'            => $txnRef,
            'vnp_PayDate'           => now()->format('YmdHis'),
        ];

        ksort($data);
        $query = http_build_query($data, '', '&', PHP_QUERY_RFC1738);
        $data['vnp_SecureHash'] = hash_hmac('sha512', $query, (string) config('services.vnpay.hash_secret'));

        return $data;
    }

    public function test_qua_han_hold_chuyen_sang_cho_xac_minh_khong_huy_ngay(): void
    {
        $booking = $this->makeBooking(BookingStatus::PENDING_DEPOSIT, [
            'deposit_expires_at' => now()->subMinute(),
        ]);
        $this->makePendingVnpayPayment($booking, 'TXNRACE001', 1000000);

        $result = $this->service()->cancelExpiredDepositBookings();

        $this->assertSame(1, $result['moved_to_grace']);
        $this->assertSame(0, $result['cancelled']);

        $booking->refresh();
        $this->assertSame(BookingStatus::EXPIRED_PENDING_CHECK, $booking->status);
        $this->assertNotNull($booking->expired_pending_check_at);
        // Payment KHÔNG bị đụng tới trong lúc đệm — vẫn PENDING chờ VNPay.
        $this->assertSame(PaymentStatus::PENDING, $booking->payment->status);
        // Vẫn tính là "đang giữ phòng" trong lúc đệm.
        $this->assertContains(BookingStatus::EXPIRED_PENDING_CHECK->value, BookingStatus::holdingStatuses());
    }

    public function test_het_luon_khoang_dem_moi_huy_han_va_nha_phong(): void
    {
        $booking = $this->makeBooking(BookingStatus::EXPIRED_PENDING_CHECK, [
            'deposit_expires_at'        => now()->subMinutes(10),
            'expired_pending_check_at'  => now()->subMinutes(10),
        ]);
        $this->makePendingVnpayPayment($booking, 'TXNRACE002', 1000000);

        $result = $this->service()->cancelExpiredDepositBookings();

        $this->assertSame(0, $result['moved_to_grace']);
        $this->assertSame(1, $result['cancelled']);

        $booking->refresh();
        $this->assertSame(BookingStatus::CANCELLED, $booking->status);
        $this->assertSame(PaymentStatus::UNPAID, $booking->payment->status);
    }

    /**
     * Kịch bản ĐÚNG mong muốn: hold hết hạn, đơn vào khoảng đệm
     * expired_pending_check, rồi VNPay báo thanh toán thành công TRONG lúc
     * đệm (job chưa kịp hủy hẳn) — phải được xác nhận bình thường vì phòng
     * chưa từng được nhả cho ai khác.
     */
    public function test_thanh_toan_thanh_cong_trong_luc_dem_van_duoc_xac_nhan(): void
    {
        $booking = $this->makeBooking(BookingStatus::PENDING_DEPOSIT, [
            'deposit_expires_at' => now()->subMinute(),
        ]);
        $this->makePendingVnpayPayment($booking, 'TXNRACE003', 1000000);

        // Job quét chạy trước — chuyển sang khoảng đệm, CHƯA hủy hẳn.
        $this->service()->cancelExpiredDepositBookings();
        $this->assertSame(BookingStatus::EXPIRED_PENDING_CHECK, $booking->fresh()->status);

        // VNPay báo thành công tới trong lúc đệm.
        $result = $this->service()->confirmVnpayReturn($this->signedSuccessQuery('TXNRACE003', 1000000));

        $this->assertTrue($result['success']);
        $this->assertSame('ok', $result['code']);

        $booking->refresh();
        $this->assertSame(BookingStatus::CONFIRMED, $booking->status);
        $this->assertSame(PaymentStatus::PAID, $booking->payment->status);
        $this->assertEquals(1000000, (float) $booking->payment->amount_collected);
        $this->assertSame(0, RefundRequest::count());
    }

    /**
     * Kịch bản lỗi gốc đã sửa: hold hết hạn + hết LUÔN khoảng đệm (đơn đã
     * cancelled hẳn), sau đó VNPay mới báo thanh toán thành công. Trước khi
     * sửa, hệ thống lặng lẽ bỏ qua (payment->status không còn PENDING nên bị
     * coi là "đã xử lý") — tiền bị trừ nhưng không ai biết. Giờ phải tạo
     * refund request, thử hoàn tiền tự động, báo khách + admin/staff, và
     * KHÔNG được "hồi sinh" lại booking đã hủy.
     */
    public function test_thanh_toan_tre_sau_khi_da_huy_han_tao_refund_request_khong_im_lang_bo_qua(): void
    {
        Notification::fake();
        Http::fake([
            '*' => Http::response(['vnp_ResponseCode' => '00', 'vnp_Message' => 'Confirm Success'], 200),
        ]);

        $admin = User::factory()->admin()->create();

        $booking = $this->makeBooking(BookingStatus::EXPIRED_PENDING_CHECK, [
            'deposit_expires_at'       => now()->subMinutes(10),
            'expired_pending_check_at' => now()->subMinutes(10),
        ]);
        $this->makePendingVnpayPayment($booking, 'TXNRACE004', 1000000);

        // Job quét chạy trước — hết luôn khoảng đệm, hủy hẳn + nhả phòng.
        $this->service()->cancelExpiredDepositBookings();
        $this->assertSame(BookingStatus::CANCELLED, $booking->fresh()->status);
        $this->assertSame(PaymentStatus::UNPAID, $booking->fresh()->payment->status);

        // VNPay báo thành công tới SAU KHI đã hủy hẳn.
        $result = $this->service()->confirmVnpayReturn($this->signedSuccessQuery('TXNRACE004', 1000000));

        $this->assertFalse($result['success']);
        $this->assertSame('refund_pending', $result['code']);

        // Booking KHÔNG bị "hồi sinh" lại — vẫn cancelled.
        $booking->refresh();
        $this->assertSame(BookingStatus::CANCELLED, $booking->status);

        // Đã tạo đúng 1 refund request, tự động hoàn tiền thành công (VNPay
        // fake trả về 00) và đồng thời báo khách + admin/staff — không lặng
        // lẽ bỏ qua như trước.
        $this->assertSame(1, RefundRequest::count());
        $refundRequest = RefundRequest::first();
        $this->assertSame($booking->id, $refundRequest->booking_id);
        $this->assertSame($booking->payment->id, $refundRequest->payment_id);
        $this->assertEquals(1000000, (float) $refundRequest->amount);
        $this->assertSame(RefundRequestStatus::REFUNDED, $refundRequest->status);

        $this->assertSame(PaymentStatus::REFUNDED, $booking->payment->status);

        Notification::assertSentTo($booking->user, OrphanedPaymentNeedsRefund::class);
        Notification::assertSentTo($admin, OrphanedPaymentNeedsRefund::class);

        // IPN gọi lại lần 2 (VNPay retry) — idempotent, KHÔNG tạo thêm refund
        // request/hoàn tiền lần 2.
        $secondResult = $this->service()->confirmVnpayReturn($this->signedSuccessQuery('TXNRACE004', 1000000));

        $this->assertFalse($secondResult['success']);
        $this->assertSame('refund_pending', $secondResult['code']);
        $this->assertSame(1, RefundRequest::count());
    }

    /**
     * Phản hồi feedback thực tế: khi hold chỉ còn ÍT hơn txn_expire_minutes
     * (VD còn 5 phút trong hold 15 phút), phiên VNPay phải nhận ĐÚNG 5 phút
     * còn lại đó (tiếp nối đồng hồ giữ chỗ) — KHÔNG được cấp lại một cửa sổ
     * 15 phút đầy đủ mới tính từ lúc bấm, nếu không đồng hồ Homi và đồng hồ
     * VNPay sẽ hiện 2 con số khác nhau dù đo cùng 1 khái niệm.
     */
    public function test_phien_vnpay_bam_theo_dung_thoi_gian_con_lai_cua_hold(): void
    {
        $customer = User::factory()->create();
        $depositExpiresAt = now()->addMinutes(5);

        $booking = Booking::factory()->create([
            'user_id'            => $customer->id,
            'status'             => BookingStatus::PENDING_DEPOSIT,
            'total_amount'       => 1000000,
            'deposit_expires_at' => $depositExpiresAt,
        ]);
        $booking->payment()->create([
            'method' => PaymentMethod::PAY_AT_HOTEL,
            'amount' => 1000000,
            'amount_collected' => 0,
            'status' => PaymentStatus::UNPAID,
        ]);

        $result = $this->service()->initiateVnpayPayment($booking->id, $customer, '127.0.0.1');

        parse_str((string) parse_url($result['payment_url'], PHP_URL_QUERY), $query);
        $expectedExpireStr = $depositExpiresAt->copy()->timezone('Asia/Ho_Chi_Minh')->format('YmdHis');

        $this->assertSame($expectedExpireStr, $query['vnp_ExpireDate']);
        $this->assertSame(
            $depositExpiresAt->timestamp,
            $booking->payment->fresh()->vnpay_session_expires_at->timestamp
        );
    }

    /**
     * Ngược lại: nếu hold còn dư RẤT NHIỀU (hơn txn_expire_minutes), phiên
     * VNPay vẫn bị giới hạn ở đúng txn_expire_minutes — không được cấp dài
     * hơn mức kỹ thuật VNPay cho phép.
     */
    public function test_phien_vnpay_khong_vuot_qua_txn_expire_minutes_khi_hold_con_du(): void
    {
        $customer = User::factory()->create();

        $booking = Booking::factory()->create([
            'user_id'            => $customer->id,
            'status'             => BookingStatus::PENDING_DEPOSIT,
            'total_amount'       => 1000000,
            'deposit_expires_at' => now()->addMinutes(60),
        ]);
        $booking->payment()->create([
            'method' => PaymentMethod::PAY_AT_HOTEL,
            'amount' => 1000000,
            'amount_collected' => 0,
            'status' => PaymentStatus::UNPAID,
        ]);

        $this->service()->initiateVnpayPayment($booking->id, $customer, '127.0.0.1');

        $expiresAt = $booking->payment->fresh()->vnpay_session_expires_at;
        $expectedMax = now()->addMinutes((int) config('services.vnpay.txn_expire_minutes', 15));

        $this->assertEqualsWithDelta($expectedMax->timestamp, $expiresAt->timestamp, 5);
    }
}
