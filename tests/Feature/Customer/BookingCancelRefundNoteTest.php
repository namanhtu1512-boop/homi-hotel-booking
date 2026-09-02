<?php

namespace Tests\Feature\Customer;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingCancelRefundNoteTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsCustomer(): User
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer);

        return $customer;
    }

    private function confirmedBookingFor(User $customer): Booking
    {
        return Booking::factory()->create([
            'user_id'      => $customer->id,
            'status'       => BookingStatus::CONFIRMED,
            'total_amount' => 2_000_000,
            'check_in'     => now()->addDays(10),
            'check_out'    => now()->addDays(11),
        ]);
    }

    public function test_dat_coc_chua_huy_hien_canh_bao_mat_coc_thay_vi_mien_phi_hoan_100(): void
    {
        $customer = $this->loginAsCustomer();
        $booking  = $this->confirmedBookingFor($customer);

        Payment::create([
            'booking_id'       => $booking->id,
            'method'           => PaymentMethod::CASH_WITH_DEPOSIT,
            'amount'           => 2_000_000,
            'amount_collected' => 0,
            'deposit_amount'   => 600_000,
            'status'           => PaymentStatus::DEPOSIT_PAID,
            'deposit_paid_at'  => now(),
        ]);

        $response = $this->get(route('customer.bookings.show', $booking->id));

        $response->assertOk();
        $response->assertSee('Hủy ngay bây giờ: tiền cọc đã đặt (600.000đ) sẽ không được hoàn.');
        $response->assertDontSee('Hủy ngay bây giờ: miễn phí, hoàn 100% (nếu đã thanh toán).');
    }

    public function test_huy_don_da_thanh_toan_thu_cong_thi_hien_dong_thong_bao_rieng_kem_so_tien_hoan(): void
    {
        // Chuyển khoản/tiền mặt không có cổng thanh toán thật để tự hoàn —
        // giờ luôn cần admin/staff bấm xác nhận thủ công (xem
        // BookingService::attemptRefund()), nên phía khách nhận nhánh
        // 'error' (không phải 'success') dù về bản chất không có gì sai —
        // chỉ là "chưa xong", đúng ý nghĩa mới của $refund['ok'].
        $customer = $this->loginAsCustomer();
        $booking  = $this->confirmedBookingFor($customer);

        Payment::create([
            'booking_id'       => $booking->id,
            'method'           => PaymentMethod::PAY_AT_HOTEL,
            'amount'           => 2_000_000,
            'amount_collected' => 2_000_000,
            'status'           => PaymentStatus::PAID,
            'paid_at'          => now(),
        ]);

        $response = $this->post(route('customer.bookings.cancel', $booking->id));

        $response->assertRedirect(route('customer.bookings.show', $booking->id));
        $response->assertSessionHas('error', 'Đã hủy đơn. Khách sạn sẽ hoàn tiền sớm nhất cho bạn.');
        $response->assertSessionHas('refund_note', 'Quý khách sẽ được hoàn lại 2.000.000đ. Chúng tôi sẽ hoàn trả trong thời gian sớm nhất có thể.');

        $follow = $this->get(route('customer.bookings.show', $booking->id));
        $follow->assertOk();
        $follow->assertSee('Đã hủy đơn. Khách sạn sẽ hoàn tiền sớm nhất cho bạn.');
        $follow->assertSee('Quý khách sẽ được hoàn lại 2.000.000đ. Chúng tôi sẽ hoàn trả trong thời gian sớm nhất có thể.');
    }

    public function test_huy_don_vnpay_tu_hoan_thanh_cong_thi_hien_dong_success(): void
    {
        Http::fake(['*' => Http::response(['vnp_ResponseCode' => '00'])]);

        $customer = $this->loginAsCustomer();
        $booking  = $this->confirmedBookingFor($customer);

        Payment::create([
            'booking_id'             => $booking->id,
            'method'                 => PaymentMethod::ONLINE_VNPAY,
            'amount'                 => 2_000_000,
            'amount_collected'       => 2_000_000,
            'last_gateway_amount'    => 2_000_000,
            'status'                 => PaymentStatus::PAID,
            'paid_at'                => now(),
            'gateway_transaction_no' => '14123456',
            'gateway_paid_at'        => now(),
            'transaction_code'       => 'HOMI-TEST-1',
        ]);

        $response = $this->post(route('customer.bookings.cancel', $booking->id));

        $response->assertRedirect(route('customer.bookings.show', $booking->id));
        $response->assertSessionHas('success', 'Đã hủy đơn đặt phòng.');
        $response->assertSessionHas('refund_note', 'Quý khách sẽ được hoàn lại 2.000.000đ. Chúng tôi sẽ hoàn trả trong thời gian sớm nhất có thể.');
    }

    public function test_huy_don_moi_dat_coc_thi_khong_hien_dong_thong_bao_hoan_tien(): void
    {
        $customer = $this->loginAsCustomer();
        $booking  = $this->confirmedBookingFor($customer);

        Payment::create([
            'booking_id'       => $booking->id,
            'method'           => PaymentMethod::CASH_WITH_DEPOSIT,
            'amount'           => 2_000_000,
            'amount_collected' => 0,
            'deposit_amount'   => 600_000,
            'status'           => PaymentStatus::DEPOSIT_PAID,
            'deposit_paid_at'  => now(),
        ]);

        $response = $this->post(route('customer.bookings.cancel', $booking->id));

        $response->assertRedirect(route('customer.bookings.show', $booking->id));
        $response->assertSessionHas('success', 'Đã hủy đơn đặt phòng.');
        $response->assertSessionMissing('refund_note');
    }

    public function test_huy_don_vnpay_thieu_thong_tin_giao_dich_van_hien_so_tien_can_hoan_thu_cong(): void
    {
        $customer = $this->loginAsCustomer();
        $booking  = $this->confirmedBookingFor($customer);

        Payment::create([
            'booking_id'             => $booking->id,
            'method'                 => PaymentMethod::ONLINE_VNPAY,
            'amount'                 => 2_000_000,
            'amount_collected'       => 2_000_000,
            'status'                 => PaymentStatus::PAID,
            'paid_at'                => now(),
            'gateway_transaction_no' => null,
        ]);

        $response = $this->post(route('customer.bookings.cancel', $booking->id));

        $response->assertRedirect(route('customer.bookings.show', $booking->id));
        $response->assertSessionHas('error', 'Đã hủy đơn. Khách sạn sẽ hoàn tiền sớm nhất cho bạn.');
        $response->assertSessionHas('refund_note', 'Quý khách sẽ được hoàn lại 2.000.000đ. Chúng tôi sẽ hoàn trả trong thời gian sớm nhất có thể.');
    }
}
