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
 * TC-ADB-015   | Admin đánh dấu hoàn thành đơn confirmed | status = completed
 * TC-ADB-016   | Không thể hoàn thành đơn pending         | Lỗi validation, không đổi trạng thái
 * TC-ADB-017   | Không thể hoàn thành đơn đã completed    | Lỗi validation, không đổi trạng thái
 * TC-ADB-018   | Không thể hủy đơn đã completed           | Lỗi validation, không đổi trạng thái
 * TC-ADB-019   | Không thể xác nhận đơn đã cancelled      | Lỗi validation, không đổi trạng thái
 * TC-ADB-020   | Customer không đánh dấu hoàn thành được  | 403
 * TC-ADB-021   | Lọc theo tên khách hàng                  | Chỉ trả đơn đúng khách hàng
 * TC-ADB-022   | Lọc theo ngày check-in                   | Chỉ trả đơn trong khoảng check-in
 * TC-ADB-023   | Lọc theo loại phòng                      | Chỉ trả đơn có loại phòng tương ứng
 * TC-ADB-024   | Danh sách hiển thị cột loại phòng         | Thấy tên loại phòng trên trang
 * TC-ADB-025   | Không đánh dấu paid khi đơn còn pending   | Lỗi validation, không đổi trạng thái
 * TC-ADB-026   | Xác nhận đơn ghi log kèm người thực hiện  | booking_status_logs.changed_by = admin
 * TC-ADB-027   | Hủy đơn ghi log kèm người thực hiện       | booking_status_logs.changed_by = admin
 * TC-ADB-028   | Hoàn thành đơn ghi log kèm người thực hiện| booking_status_logs.changed_by = admin
 */
class AdminBookingManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function makeBooking(
        string $status = 'pending',
        ?string $paymentStatus = 'unpaid',
        ?string $customerName = null,
        ?RoomType $roomType = null,
        ?string $checkIn = null,
    ): Booking {
        $roomType ??= RoomType::factory()->create(['price_per_night' => 1000000]);
        $customer = User::factory()->customer()->create();
        $checkIn ??= now()->addDays(5)->format('Y-m-d');

        $booking = Booking::create([
            'booking_code'   => 'TEST-' . uniqid(),
            'user_id'        => $customer->id,
            'check_in'       => $checkIn,
            'check_out'      => date('Y-m-d', strtotime($checkIn . ' +2 days')),
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

    public function test_admin_can_complete_confirmed_booking(): void
    {
        $booking = $this->makeBooking('confirmed');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->post("/admin/bookings/{$booking->id}/complete");

        $response->assertRedirect(route('admin.bookings.show', $booking->id));
        $this->assertEquals('completed', $booking->fresh()->status->value);
    }

    public function test_admin_cannot_complete_pending_booking(): void
    {
        $booking = $this->makeBooking('pending');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->post("/admin/bookings/{$booking->id}/complete");

        $response->assertSessionHasErrors('status');
        $this->assertEquals('pending', $booking->fresh()->status->value);
    }

    public function test_admin_cannot_complete_already_completed_booking(): void
    {
        $booking = $this->makeBooking('completed');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->post("/admin/bookings/{$booking->id}/complete");

        $response->assertSessionHasErrors('status');
        $this->assertEquals('completed', $booking->fresh()->status->value);
    }

    public function test_admin_cannot_cancel_completed_booking(): void
    {
        $booking = $this->makeBooking('completed');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->post("/admin/bookings/{$booking->id}/cancel");

        $response->assertSessionHasErrors('status');
        $this->assertEquals('completed', $booking->fresh()->status->value);
    }

    public function test_admin_cannot_confirm_cancelled_booking(): void
    {
        $booking = $this->makeBooking('cancelled');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->post("/admin/bookings/{$booking->id}/confirm");

        $response->assertSessionHasErrors('status');
        $this->assertEquals('cancelled', $booking->fresh()->status->value);
    }

    public function test_customer_cannot_complete_booking(): void
    {
        $booking = $this->makeBooking('confirmed');

        $this->actingAs($this->makeUser('customer'))
            ->post("/admin/bookings/{$booking->id}/complete")
            ->assertRedirect(route('customer.dashboard'));

        $this->assertEquals('confirmed', $booking->fresh()->status->value);
    }

    public function test_admin_can_filter_bookings_by_customer_name(): void
    {
        $this->makeBooking('pending', customerName: 'Nguyễn Văn A');
        $this->makeBooking('pending', customerName: 'Trần Thị B');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->get('/admin/bookings?customer_name=' . urlencode('Nguyễn'));

        $response->assertOk();
        $response->assertSee('Nguyễn Văn A');
        $response->assertDontSee('Trần Thị B');
    }

    public function test_admin_can_filter_bookings_by_check_in_date_range(): void
    {
        $inRange = $this->makeBooking('pending', checkIn: now()->addDays(10)->format('Y-m-d'));
        $outOfRange = $this->makeBooking('pending', checkIn: now()->addDays(30)->format('Y-m-d'));

        $response = $this->actingAsAdmin($this->makeUser('admin'))->get('/admin/bookings?' . http_build_query([
            'check_in_from' => now()->addDays(8)->format('Y-m-d'),
            'check_in_to'   => now()->addDays(15)->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee($inRange->booking_code);
        $response->assertDontSee($outOfRange->booking_code);
    }

    public function test_admin_can_filter_bookings_by_room_type(): void
    {
        $roomTypeA = RoomType::factory()->create(['name' => 'Phòng Deluxe']);
        $roomTypeB = RoomType::factory()->create(['name' => 'Phòng Suite']);

        $bookingA = $this->makeBooking('pending', roomType: $roomTypeA);
        $bookingB = $this->makeBooking('pending', roomType: $roomTypeB);

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->get("/admin/bookings?room_type_id={$roomTypeA->id}");

        $response->assertOk();
        $response->assertSee($bookingA->booking_code);
        $response->assertDontSee($bookingB->booking_code);
    }

    public function test_bookings_list_shows_room_type_column(): void
    {
        $roomType = RoomType::factory()->create(['name' => 'Phòng Deluxe']);
        $this->makeBooking('pending', roomType: $roomType);

        $response = $this->actingAsAdmin($this->makeUser('admin'))->get('/admin/bookings');

        $response->assertOk();
        $response->assertSee('Phòng Deluxe');
    }

    public function test_admin_cannot_mark_payment_paid_while_booking_pending(): void
    {
        $booking = $this->makeBooking('pending', 'unpaid');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/bookings/{$booking->id}/payment", ['status' => 'paid']);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('unpaid', $booking->fresh()->payment->status->value);
    }

    public function test_confirming_booking_logs_who_performed_it(): void
    {
        $booking = $this->makeBooking('pending');
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)->post("/admin/bookings/{$booking->id}/confirm");

        $log = $booking->fresh()->statusLogs->last();
        $this->assertEquals($admin->id, $log->changed_by);
    }

    public function test_cancelling_booking_logs_who_performed_it(): void
    {
        $booking = $this->makeBooking('pending');
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)->post("/admin/bookings/{$booking->id}/cancel");

        $log = $booking->fresh()->statusLogs->last();
        $this->assertEquals($admin->id, $log->changed_by);
    }

    public function test_completing_booking_logs_who_performed_it(): void
    {
        $booking = $this->makeBooking('confirmed');
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)->post("/admin/bookings/{$booking->id}/complete");

        $log = $booking->fresh()->statusLogs->last();
        $this->assertEquals($admin->id, $log->changed_by);
    }
}
