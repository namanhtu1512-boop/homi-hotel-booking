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
 * Test trang quản lý thanh toán phía admin (/admin/payments).
 *
 * Test case ID | Chức năng                                 | Kết quả mong đợi
 * TC-ADP-001   | Admin xem danh sách thanh toán             | 200
 * TC-ADP-002   | Staff xem danh sách thanh toán             | 200
 * TC-ADP-003   | Customer xem danh sách thanh toán admin    | 403
 * TC-ADP-004   | Guest xem danh sách thanh toán admin       | redirect admin.login
 * TC-ADP-005   | Lọc theo trạng thái thanh toán              | Chỉ trả đúng trạng thái
 * TC-ADP-006   | Lọc theo mã đơn                             | Chỉ trả đúng đơn
 * TC-ADP-007   | Lọc theo tên khách hàng                     | Chỉ trả đúng khách hàng
 * TC-ADP-008   | Admin đánh dấu đã thanh toán từ trang payments | payment = paid, có paid_at
 * TC-ADP-009   | Admin đánh dấu đã hoàn tiền từ trang payments  | payment = refunded
 * TC-ADP-010   | Không cho refund trực tiếp từ unpaid         | Lỗi validation
 * TC-ADP-011   | Không đánh dấu paid khi đơn còn pending      | Lỗi validation
 */
class AdminPaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function makeBooking(string $status = 'confirmed', ?string $paymentStatus = 'unpaid', ?string $customerName = null): Booking
    {
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $customer = User::factory()->customer()->create();

        $booking = Booking::create([
            'booking_code'   => 'TEST-' . uniqid(),
            'user_id'        => $customer->id,
            'check_in'       => now()->addDays(5)->format('Y-m-d'),
            'check_out'      => now()->addDays(7)->format('Y-m-d'),
            'nights'         => 2,
            'customer_name'  => $customerName ?? 'Khách Test',
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

        if ($paymentStatus) {
            Payment::create([
                'booking_id' => $booking->id,
                'method'     => 'pay_at_hotel',
                'amount'     => 2000000,
                'status'     => $paymentStatus,
                'paid_at'    => $paymentStatus === 'paid' ? now() : null,
            ]);
        }

        return $booking;
    }

    public function test_admin_can_view_payments_list(): void
    {
        $this->makeBooking();

        $this->actingAsAdmin($this->makeUser('admin'))
            ->get('/admin/payments')
            ->assertOk();
    }

    public function test_staff_can_view_payments_list(): void
    {
        $this->actingAsAdmin($this->makeUser('staff'))
            ->get('/admin/payments')
            ->assertOk();
    }

    public function test_customer_cannot_view_admin_payments_list(): void
    {
        $this->actingAs($this->makeUser('customer'))
            ->get('/admin/payments')
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_guest_is_redirected_to_admin_login_for_payments_list(): void
    {
        $this->get('/admin/payments')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_filter_payments_by_status(): void
    {
        $this->makeBooking('confirmed', 'unpaid');
        $this->makeBooking('confirmed', 'paid');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->get('/admin/payments?status=paid');

        $response->assertOk();
        $response->assertSee('Đã thanh toán');
    }

    public function test_admin_can_filter_payments_by_booking_code(): void
    {
        $target = $this->makeBooking('confirmed', 'unpaid');
        $other = $this->makeBooking('confirmed', 'unpaid');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->get("/admin/payments?booking_code={$target->booking_code}");

        $response->assertOk();
        $response->assertSee($target->booking_code);
        $response->assertDontSee($other->booking_code);
    }

    public function test_admin_can_filter_payments_by_customer_name(): void
    {
        $this->makeBooking('confirmed', 'unpaid', 'Nguyễn Văn A');
        $this->makeBooking('confirmed', 'unpaid', 'Trần Thị B');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->get('/admin/payments?customer_name=' . urlencode('Nguyễn'));

        $response->assertOk();
        $response->assertSee('Nguyễn Văn A');
        $response->assertDontSee('Trần Thị B');
    }

    public function test_admin_can_mark_payment_as_paid_from_payments_page(): void
    {
        $booking = $this->makeBooking('confirmed', 'unpaid');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/payments/{$booking->payment->id}/status", ['status' => 'paid']);

        $response->assertRedirect(route('admin.payments.index'));
        $payment = $booking->fresh()->payment;
        $this->assertEquals('paid', $payment->status->value);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_admin_can_mark_payment_as_refunded_from_payments_page(): void
    {
        $booking = $this->makeBooking('cancelled', 'paid');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/payments/{$booking->payment->id}/status", ['status' => 'refunded']);

        $response->assertRedirect(route('admin.payments.index'));
        $this->assertEquals('refunded', $booking->fresh()->payment->status->value);
    }

    public function test_admin_cannot_refund_directly_from_unpaid_on_payments_page(): void
    {
        $booking = $this->makeBooking('pending', 'unpaid');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/payments/{$booking->payment->id}/status", ['status' => 'refunded']);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('unpaid', $booking->fresh()->payment->status->value);
    }

    public function test_admin_cannot_mark_paid_while_booking_pending_on_payments_page(): void
    {
        $booking = $this->makeBooking('pending', 'unpaid');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/payments/{$booking->payment->id}/status", ['status' => 'paid']);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('unpaid', $booking->fresh()->payment->status->value);
    }

    public function test_customer_cannot_update_payment_via_payments_page(): void
    {
        $booking = $this->makeBooking('confirmed', 'unpaid');

        $this->actingAs($this->makeUser('customer'))
            ->patch("/admin/payments/{$booking->payment->id}/status", ['status' => 'paid'])
            ->assertRedirect(route('customer.dashboard'));

        $this->assertEquals('unpaid', $booking->fresh()->payment->status->value);
    }
}
