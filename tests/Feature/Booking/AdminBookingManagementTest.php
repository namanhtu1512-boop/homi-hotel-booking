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
 * Test quản lý đơn đặt phòng phía admin: xác nhận, hủy, cập nhật thanh toán.
 *
 * Test case ID | Chức năng                              | Kết quả mong đợi
 * TC-ADB-001   | Admin xem danh sách đơn                | 200
 * TC-ADB-002   | Staff xem danh sách đơn                | 200
 * TC-ADB-003   | Customer xem danh sách đơn admin       | 403
 * TC-ADB-004   | Guest xem danh sách đơn admin          | redirect admin.login
 * TC-ADB-005   | Lọc theo trạng thái                    | Chỉ trả đơn đúng trạng thái
 * TC-ADB-006   | Admin xem chi tiết đơn                 | 200
 * TC-ADB-007   | Admin xác nhận đơn pending              | status = confirmed
 * TC-ADB-008   | Admin xác nhận đơn đã confirmed         | Lỗi validation, không đổi trạng thái
 * TC-ADB-009   | Admin hủy đơn pending                   | status = cancelled
 * TC-ADB-010   | Hủy đơn đã thanh toán tự hoàn tiền      | payment = refunded
 * TC-ADB-011   | Admin đánh dấu đã thanh toán            | payment = paid, có paid_at
 * TC-ADB-012   | Admin đánh dấu đã hoàn tiền (từ paid)   | payment = refunded
 * TC-ADB-013   | Không cho refund trực tiếp từ unpaid    | Lỗi validation
 * TC-ADB-014   | Customer không xác nhận được đơn        | 403
 */
class AdminBookingManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function makeBooking(string $status = 'pending', ?string $paymentStatus = 'unpaid'): Booking
    {
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $customer = User::factory()->customer()->create();

        $booking = Booking::create([
            'booking_code'   => 'TEST-' . uniqid(),
            'user_id'        => $customer->id,
            'check_in'       => now()->addDays(5)->format('Y-m-d'),
            'check_out'      => now()->addDays(7)->format('Y-m-d'),
            'nights'         => 2,
            'customer_name'  => 'Khách Test',
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

    public function test_admin_can_view_bookings_list(): void
    {
        $this->makeBooking();

        $this->actingAsAdmin($this->makeUser('admin'))
            ->get('/admin/bookings')
            ->assertOk();
    }

    public function test_staff_can_view_bookings_list(): void
    {
        $this->actingAsAdmin($this->makeUser('staff'))
            ->get('/admin/bookings')
            ->assertOk();
    }

    public function test_customer_cannot_view_admin_bookings_list(): void
    {
        $this->actingAs($this->makeUser('customer'))
            ->get('/admin/bookings')
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_guest_is_redirected_to_admin_login_for_bookings_list(): void
    {
        $this->get('/admin/bookings')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_filter_bookings_by_status(): void
    {
        $this->makeBooking('pending');
        $this->makeBooking('confirmed');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->get('/admin/bookings?status=confirmed');

        $response->assertOk();
        $response->assertSee('Đã xác nhận');
    }

    public function test_admin_can_view_booking_detail(): void
    {
        $booking = $this->makeBooking();

        $this->actingAsAdmin($this->makeUser('admin'))
            ->get("/admin/bookings/{$booking->id}")
            ->assertOk()
            ->assertSee($booking->booking_code);
    }

    public function test_admin_can_confirm_pending_booking(): void
    {
        $booking = $this->makeBooking('pending');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->post("/admin/bookings/{$booking->id}/confirm");

        $response->assertRedirect(route('admin.bookings.show', $booking->id));
        $this->assertEquals('confirmed', $booking->fresh()->status->value);
    }

    public function test_admin_cannot_confirm_already_confirmed_booking(): void
    {
        $booking = $this->makeBooking('confirmed');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->post("/admin/bookings/{$booking->id}/confirm");

        $response->assertSessionHasErrors('status');
        $this->assertEquals('confirmed', $booking->fresh()->status->value);
    }

    public function test_admin_can_cancel_pending_booking(): void
    {
        $booking = $this->makeBooking('pending');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->post("/admin/bookings/{$booking->id}/cancel");

        $response->assertRedirect(route('admin.bookings.show', $booking->id));
        $this->assertEquals('cancelled', $booking->fresh()->status->value);
    }

    public function test_cancelling_paid_booking_auto_refunds_payment(): void
    {
        $booking = $this->makeBooking('confirmed', 'paid');

        $this->actingAsAdmin($this->makeUser('admin'))
            ->post("/admin/bookings/{$booking->id}/cancel");

        $this->assertEquals('refunded', $booking->fresh()->payment->status->value);
    }

    public function test_cancelling_paid_booking_logs_refund_history(): void
    {
        $booking = $this->makeBooking('confirmed', 'paid');

        $this->actingAsAdmin($this->makeUser('admin'))
            ->post("/admin/bookings/{$booking->id}/cancel");

        $log = $booking->fresh()->payment->statusLogs->last();
        $this->assertEquals('paid', $log->from_status->value);
        $this->assertEquals('refunded', $log->to_status->value);
    }

    public function test_admin_can_mark_payment_as_paid(): void
    {
        $booking = $this->makeBooking('confirmed', 'unpaid');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/bookings/{$booking->id}/payment", ['status' => 'paid']);

        $response->assertRedirect(route('admin.bookings.show', $booking->id));
        $payment = $booking->fresh()->payment;
        $this->assertEquals('paid', $payment->status->value);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_marking_payment_as_paid_logs_history_with_admin_who_did_it(): void
    {
        $booking = $this->makeBooking('confirmed', 'unpaid');
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)
            ->patch("/admin/bookings/{$booking->id}/payment", ['status' => 'paid']);

        $log = $booking->fresh()->payment->statusLogs->last();
        $this->assertEquals('unpaid', $log->from_status->value);
        $this->assertEquals('paid', $log->to_status->value);
        $this->assertEquals($admin->id, $log->changed_by);
    }

    public function test_creating_booking_logs_initial_payment_status(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 3, 'price_per_night' => 1000000]);
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->post('/customer/bookings', [
            'room_type_id'   => $roomType->id,
            'check_in'       => now()->addDays(5)->format('Y-m-d'),
            'check_out'      => now()->addDays(7)->format('Y-m-d'),
            'quantity'       => 1,
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
        ]);

        $booking = $customer->bookings()->first();
        $log = $booking->payment->statusLogs->first();

        $this->assertNotNull($log);
        $this->assertNull($log->from_status);
        $this->assertEquals('unpaid', $log->to_status->value);
        $this->assertEquals($customer->id, $log->changed_by);
    }

    public function test_admin_can_mark_paid_payment_as_refunded(): void
    {
        $booking = $this->makeBooking('cancelled', 'paid');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/bookings/{$booking->id}/payment", ['status' => 'refunded']);

        $response->assertRedirect(route('admin.bookings.show', $booking->id));
        $this->assertEquals('refunded', $booking->fresh()->payment->status->value);
    }

    public function test_admin_cannot_refund_directly_from_unpaid(): void
    {
        $booking = $this->makeBooking('pending', 'unpaid');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/bookings/{$booking->id}/payment", ['status' => 'refunded']);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('unpaid', $booking->fresh()->payment->status->value);
    }

    public function test_customer_cannot_confirm_booking(): void
    {
        $booking = $this->makeBooking('pending');

        $this->actingAs($this->makeUser('customer'))
            ->post("/admin/bookings/{$booking->id}/confirm")
            ->assertRedirect(route('customer.dashboard'));

        $this->assertEquals('pending', $booking->fresh()->status->value);
    }
}
