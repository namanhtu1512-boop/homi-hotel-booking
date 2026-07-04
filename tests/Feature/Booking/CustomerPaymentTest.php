<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Thanh toán tự phục vụ của khách (online demo / báo chuyển khoản) — chỉ
 * khả dụng khi đơn đã được admin/staff xác nhận (Booking::canMarkPaymentAsPaid()).
 */
class CustomerPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(User $customer, string $status, string $paymentStatus = 'unpaid'): Booking
    {
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $checkIn  = now()->addDays(5)->format('Y-m-d');

        $booking = Booking::create([
            'booking_code'   => 'TEST-' . uniqid(),
            'user_id'        => $customer->id,
            'check_in'       => $checkIn,
            'check_out'      => now()->addDays(7)->format('Y-m-d'),
            'nights'         => 2,
            'customer_name'  => $customer->name,
            'customer_phone' => '0900000000',
            'total_amount'   => 2000000,
            'status'         => $status,
        ]);

        BookingItem::create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomType->id,
            'quantity'        => 1,
            'price_per_night' => $roomType->price_per_night,
            'nights'          => 2,
            'subtotal'        => 2000000,
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'method'     => 'pay_at_hotel',
            'amount'     => 2000000,
            'status'     => $paymentStatus,
        ]);

        return $booking->fresh('payment');
    }

    public function test_customer_cannot_pay_online_when_booking_not_confirmed(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = $this->makeBooking($customer, 'pending');

        $response = $this->actingAs($customer)
            ->post(route('customer.bookings.pay-online', $booking->id));

        $response->assertSessionHasErrors('status');
        $this->assertEquals('unpaid', $booking->fresh()->payment->status->value);
    }

    public function test_customer_can_pay_online_when_confirmed(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = $this->makeBooking($customer, 'confirmed');

        $response = $this->actingAs($customer)
            ->post(route('customer.bookings.pay-online', $booking->id));

        $response->assertRedirect(route('customer.bookings.show', $booking->id));

        $payment = $booking->fresh('payment')->payment;
        $this->assertEquals('paid', $payment->status->value);
        $this->assertEquals('online_demo', $payment->method->value);
        $this->assertNotNull($payment->transaction_code);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_customer_can_report_bank_transfer_when_confirmed(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = $this->makeBooking($customer, 'confirmed');

        $response = $this->actingAs($customer)
            ->post(route('customer.bookings.pay-bank-transfer', $booking->id));

        $response->assertRedirect(route('customer.bookings.show', $booking->id));

        $payment = $booking->fresh('payment')->payment;
        $this->assertEquals('pending', $payment->status->value);
        $this->assertEquals('bank_transfer', $payment->method->value);
        $this->assertNull($payment->paid_at);
    }

    public function test_admin_can_still_confirm_a_reported_bank_transfer(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = $this->makeBooking($customer, 'confirmed');
        $this->actingAs($customer)->post(route('customer.bookings.pay-bank-transfer', $booking->id));

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->withSession(['login_context' => 'admin'])
            ->patch(route('admin.bookings.update-payment', $booking->id), ['status' => 'paid'])
            ->assertRedirect();

        $this->assertEquals('paid', $booking->fresh('payment')->payment->status->value);
    }

    public function test_customer_cannot_pay_for_another_customers_booking(): void
    {
        $owner    = User::factory()->customer()->create();
        $intruder = User::factory()->customer()->create();
        $booking  = $this->makeBooking($owner, 'confirmed');

        $this->actingAs($intruder)
            ->post(route('customer.bookings.pay-online', $booking->id))
            ->assertForbidden();

        $this->assertEquals('unpaid', $booking->fresh('payment')->payment->status->value);
    }

    public function test_customer_cannot_pay_online_when_already_paid(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = $this->makeBooking($customer, 'confirmed', 'paid');

        $this->actingAs($customer)
            ->post(route('customer.bookings.pay-online', $booking->id))
            ->assertSessionHasErrors('status');
    }

    // ----------------------------------------------------------------
    // Đặt cọc 30% — tiền mặt khi nhận phòng
    // ----------------------------------------------------------------

    public function test_customer_can_pay_deposit_when_confirmed(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = $this->makeBooking($customer, 'confirmed'); // total_amount = 2,000,000

        $response = $this->actingAs($customer)
            ->post(route('customer.bookings.pay-deposit', $booking->id));

        $response->assertRedirect(route('customer.bookings.show', $booking->id));

        $payment = $booking->fresh('payment')->payment;
        $this->assertEquals('deposit_paid', $payment->status->value);
        $this->assertEquals('cash_with_deposit', $payment->method->value);
        $this->assertEquals(600000, (float) $payment->deposit_amount); // 30% of 2,000,000
        $this->assertNotNull($payment->deposit_transaction_code);
        $this->assertNotNull($payment->deposit_paid_at);
        // Chưa thu đủ toàn bộ — paid_at (thanh toán đủ) vẫn null.
        $this->assertNull($payment->paid_at);
    }

    public function test_customer_cannot_pay_deposit_when_booking_not_confirmed(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = $this->makeBooking($customer, 'pending');

        $this->actingAs($customer)
            ->post(route('customer.bookings.pay-deposit', $booking->id))
            ->assertSessionHasErrors('status');

        $this->assertEquals('unpaid', $booking->fresh('payment')->payment->status->value);
    }

    public function test_customer_cannot_pay_deposit_twice(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = $this->makeBooking($customer, 'confirmed');

        $this->actingAs($customer)->post(route('customer.bookings.pay-deposit', $booking->id));

        $this->actingAs($customer)
            ->post(route('customer.bookings.pay-deposit', $booking->id))
            ->assertSessionHasErrors('status');
    }

    public function test_admin_can_mark_fully_paid_after_deposit(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = $this->makeBooking($customer, 'confirmed');
        $this->actingAs($customer)->post(route('customer.bookings.pay-deposit', $booking->id));

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->withSession(['login_context' => 'admin'])
            ->patch(route('admin.bookings.update-payment', $booking->id), ['status' => 'paid'])
            ->assertRedirect();

        $payment = $booking->fresh('payment')->payment;
        $this->assertEquals('paid', $payment->status->value);
        $this->assertNotNull($payment->paid_at);
        // Thông tin cọc vẫn được giữ lại làm lịch sử, không bị xóa.
        $this->assertEquals(600000, (float) $payment->deposit_amount);
    }

    public function test_deposit_is_not_auto_refunded_when_admin_cancels_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $booking  = $this->makeBooking($customer, 'confirmed');
        $this->actingAs($customer)->post(route('customer.bookings.pay-deposit', $booking->id));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->withSession(['login_context' => 'admin'])
            ->post(route('admin.bookings.cancel', $booking->id))
            ->assertRedirect();

        // canRefund() chỉ đúng với PAID — cọc (deposit_paid) không tự động hoàn.
        $this->assertEquals('deposit_paid', $booking->fresh('payment')->payment->status->value);
    }
}
