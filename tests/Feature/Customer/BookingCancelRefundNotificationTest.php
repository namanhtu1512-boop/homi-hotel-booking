<?php

namespace Tests\Feature\Customer;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\BookingCancelRefundNeeded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingCancelRefundNotificationTest extends TestCase
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

    public function test_huy_don_da_thanh_toan_tien_mat_thi_bao_admin_staff_can_hoan_tien(): void
    {
        Notification::fake();

        $customer = $this->loginAsCustomer();
        $admin    = User::factory()->admin()->create();
        $staff    = User::factory()->staff()->create();
        $booking  = $this->confirmedBookingFor($customer);

        Payment::create([
            'booking_id'       => $booking->id,
            'method'           => PaymentMethod::PAY_AT_HOTEL,
            'amount'           => 2_000_000,
            'amount_collected' => 2_000_000,
            'status'           => PaymentStatus::PAID,
            'paid_at'          => now(),
        ]);

        $this->post(route('customer.bookings.cancel', $booking->id));

        Notification::assertSentTo($admin, BookingCancelRefundNeeded::class, function ($notification) use ($booking) {
            return $notification->booking->id === $booking->id
                && $notification->amount === 2_000_000.0
                && $notification->refundOk === false;
        });
        Notification::assertSentTo($staff, BookingCancelRefundNeeded::class);
        Notification::assertNotSentTo($customer, BookingCancelRefundNeeded::class);
    }

    public function test_huy_don_vnpay_hoan_tu_dong_thanh_cong_thi_khong_can_bao(): void
    {
        Notification::fake();
        Http::fake(['*' => Http::response(['vnp_ResponseCode' => '00'])]);

        $customer = $this->loginAsCustomer();
        $admin    = User::factory()->admin()->create();
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

        $this->post(route('customer.bookings.cancel', $booking->id));

        Notification::assertNotSentTo($admin, BookingCancelRefundNeeded::class);
    }

    public function test_huy_don_moi_dat_coc_thi_khong_can_bao_hoan_tien(): void
    {
        Notification::fake();

        $customer = $this->loginAsCustomer();
        $admin    = User::factory()->admin()->create();
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

        $this->post(route('customer.bookings.cancel', $booking->id));

        Notification::assertNotSentTo($admin, BookingCancelRefundNeeded::class);
    }
}
