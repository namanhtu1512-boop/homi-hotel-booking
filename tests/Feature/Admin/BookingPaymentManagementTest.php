<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * BE4 — Test tổng hợp cho luồng admin quản lý booking/payment:
 * permission, state transition, payment status rule, và filter/list.
 *
 * Test case ID | Nhóm        | Chức năng                                        | Kết quả mong đợi
 * TC-BPM-001   | Permission  | Admin xem /admin/bookings, /admin/payments       | 200
 * TC-BPM-002   | Permission  | Staff xem /staff/bookings, /staff/payments       | 200
 * TC-BPM-003   | Permission  | Customer bị chặn khỏi 2 trang trên               | redirect customer.dashboard
 * TC-BPM-004   | Permission  | Guest bị chặn khỏi 2 trang trên                  | redirect admin.login
 * TC-BPM-005   | Permission  | Customer không thực hiện được các action admin   | 403/redirect, không đổi trạng thái
 * TC-BPM-006   | Transition  | 4 transition hợp lệ (pending->confirmed,         | đổi đúng trạng thái
 *              |             | pending->cancelled, confirmed->completed,       |
 *              |             | confirmed->cancelled)                            |
 * TC-BPM-007   | Transition  | Mọi transition khác đều bị chặn                  | 422/session error, không đổi trạng thái
 * TC-BPM-008   | Payment     | unpaid/pending -> paid khi booking confirmed     | payment = paid, có paid_at
 * TC-BPM-009   | Payment     | paid -> refunded                                 | payment = refunded
 * TC-BPM-010   | Payment     | Các transition thanh toán sai bị chặn            | lỗi validation, không đổi trạng thái
 * TC-BPM-011   | Payment     | Không mark paid khi booking chưa confirmed       | lỗi validation
 * TC-BPM-012   | Payment     | Booking không có payment record                 | lỗi validation rõ ràng
 * TC-BPM-013   | Filter      | Lọc booking theo trạng thái                      | đúng kết quả
 * TC-BPM-014   | Filter      | Lọc booking theo ngày đặt (created_at)           | đúng kết quả
 * TC-BPM-015   | Filter      | Lọc booking theo ngày check-in                   | đúng kết quả
 * TC-BPM-016   | Filter      | Lọc booking theo tên khách hàng                  | đúng kết quả
 * TC-BPM-017   | Filter      | Lọc booking theo loại phòng                      | đúng kết quả
 * TC-BPM-018   | Filter      | Lọc payments theo trạng thái/mã đơn/khách hàng   | đúng kết quả
 */
class BookingPaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------

    private function makeUser(string $role): User
    {
        return match ($role) {
            'admin' => User::factory()->admin()->create(),
            'staff' => User::factory()->staff()->create(),
            default => User::factory()->customer()->create(),
        };
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

        if ($paymentStatus !== null) {
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

    // ------------------------------------------------------------
    // 1. PERMISSION
    // ------------------------------------------------------------

    public function test_admin_can_access_bookings_and_payments_pages(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)->get('/admin/bookings')->assertOk();
        $this->actingAsAdmin($admin)->get('/admin/payments')->assertOk();
    }

    public function test_staff_can_access_bookings_and_payments_pages(): void
    {
        // Staff dùng khu vực /staff/* riêng, không còn /admin/bookings,/payments.
        $staff = $this->makeUser('staff');

        $this->actingAsAdmin($staff)->get('/staff/bookings')->assertOk();
        $this->actingAsAdmin($staff)->get('/staff/payments')->assertOk();
    }

    public function test_customer_is_blocked_from_bookings_and_payments_pages(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)->get('/admin/bookings')->assertRedirect(route('customer.dashboard'));
        $this->actingAs($customer)->get('/admin/payments')->assertRedirect(route('customer.dashboard'));
    }

    public function test_guest_is_blocked_from_bookings_and_payments_pages(): void
    {
        $this->get('/admin/bookings')->assertRedirect(route('admin.login'));
        $this->get('/admin/payments')->assertRedirect(route('admin.login'));
    }

    public function test_customer_cannot_confirm_cancel_complete_or_update_payment(): void
    {
        $booking = $this->makeBooking('pending', 'unpaid');
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)
            ->post("/admin/bookings/{$booking->id}/confirm")
            ->assertRedirect(route('customer.dashboard'));
        $this->assertEquals('pending', $booking->fresh()->status->value);

        $this->actingAs($customer)
            ->post("/admin/bookings/{$booking->id}/cancel")
            ->assertRedirect(route('customer.dashboard'));
        $this->assertEquals('pending', $booking->fresh()->status->value);

        $confirmedBooking = $this->makeBooking('confirmed', 'unpaid');
        $this->actingAs($customer)
            ->post("/admin/bookings/{$confirmedBooking->id}/complete")
            ->assertRedirect(route('customer.dashboard'));
        $this->assertEquals('confirmed', $confirmedBooking->fresh()->status->value);

        $this->actingAs($customer)
            ->patch("/admin/bookings/{$confirmedBooking->id}/payment", ['status' => 'paid'])
            ->assertRedirect(route('customer.dashboard'));
        $this->assertEquals('unpaid', $confirmedBooking->fresh()->payment->status->value);

        $this->actingAs($customer)
            ->patch("/admin/payments/{$confirmedBooking->payment->id}/status", ['status' => 'paid'])
            ->assertRedirect(route('customer.dashboard'));
        $this->assertEquals('unpaid', $confirmedBooking->fresh()->payment->status->value);
    }

    public function test_guest_cannot_confirm_booking(): void
    {
        $booking = $this->makeBooking('pending');

        $this->post("/admin/bookings/{$booking->id}/confirm")->assertRedirect(route('admin.login'));
        $this->assertEquals('pending', $booking->fresh()->status->value);
    }

    // ------------------------------------------------------------
    // 2. STATE TRANSITION
    // ------------------------------------------------------------

    public static function validBookingTransitionProvider(): array
    {
        return [
            'pending -> confirmed (confirm)'   => ['pending', 'confirm', 'confirmed'],
            'pending -> cancelled (cancel)'     => ['pending', 'cancel', 'cancelled'],
            'confirmed -> completed (complete)' => ['confirmed', 'complete', 'completed'],
            'confirmed -> cancelled (cancel)'   => ['confirmed', 'cancel', 'cancelled'],
        ];
    }

    #[DataProvider('validBookingTransitionProvider')]
    public function test_valid_booking_transition_succeeds(string $initialStatus, string $action, string $expectedStatus): void
    {
        $booking = $this->makeBooking($initialStatus);

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->post("/admin/bookings/{$booking->id}/{$action}");

        $response->assertRedirect(route('admin.bookings.show', $booking->id));
        $this->assertEquals($expectedStatus, $booking->fresh()->status->value);
    }

    public static function invalidBookingTransitionProvider(): array
    {
        return [
            'confirm: already confirmed'  => ['confirmed', 'confirm', 'confirmed'],
            'confirm: cancelled'          => ['cancelled', 'confirm', 'cancelled'],
            'confirm: completed'          => ['completed', 'confirm', 'completed'],
            'confirm: checked_in'         => ['checked_in', 'confirm', 'checked_in'],
            'confirm: checked_out'        => ['checked_out', 'confirm', 'checked_out'],
            'cancel: already cancelled'   => ['cancelled', 'cancel', 'cancelled'],
            'cancel: completed'           => ['completed', 'cancel', 'completed'],
            'cancel: checked_in'          => ['checked_in', 'cancel', 'checked_in'],
            'cancel: checked_out'         => ['checked_out', 'cancel', 'checked_out'],
            'complete: pending'           => ['pending', 'complete', 'pending'],
            'complete: cancelled'         => ['cancelled', 'complete', 'cancelled'],
            'complete: already completed' => ['completed', 'complete', 'completed'],
            'complete: checked_in'        => ['checked_in', 'complete', 'checked_in'],
            'complete: checked_out'       => ['checked_out', 'complete', 'checked_out'],
        ];
    }

    #[DataProvider('invalidBookingTransitionProvider')]
    public function test_invalid_booking_transition_is_blocked(string $initialStatus, string $action, string $expectedStatus): void
    {
        $booking = $this->makeBooking($initialStatus);

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->post("/admin/bookings/{$booking->id}/{$action}");

        $response->assertSessionHasErrors('status');
        $this->assertEquals($expectedStatus, $booking->fresh()->status->value);
    }

    public function test_confirm_cancel_complete_all_record_status_log_with_actor_and_timestamp(): void
    {
        $admin = $this->makeUser('admin');

        $confirmBooking = $this->makeBooking('pending');
        $this->actingAsAdmin($admin)->post("/admin/bookings/{$confirmBooking->id}/confirm");
        $confirmLog = $confirmBooking->fresh()->statusLogs->last();
        $this->assertEquals($admin->id, $confirmLog->changed_by);
        $this->assertEquals('pending', $confirmLog->from_status->value);
        $this->assertEquals('confirmed', $confirmLog->to_status->value);
        $this->assertNotNull($confirmLog->created_at);

        $cancelBooking = $this->makeBooking('pending');
        $this->actingAsAdmin($admin)->post("/admin/bookings/{$cancelBooking->id}/cancel");
        $cancelLog = $cancelBooking->fresh()->statusLogs->last();
        $this->assertEquals($admin->id, $cancelLog->changed_by);
        $this->assertEquals('cancelled', $cancelLog->to_status->value);

        $completeBooking = $this->makeBooking('confirmed');
        $this->actingAsAdmin($admin)->post("/admin/bookings/{$completeBooking->id}/complete");
        $completeLog = $completeBooking->fresh()->statusLogs->last();
        $this->assertEquals($admin->id, $completeLog->changed_by);
        $this->assertEquals('completed', $completeLog->to_status->value);
    }

    // ------------------------------------------------------------
    // 3. PAYMENT STATUS
    // ------------------------------------------------------------

    public function test_admin_can_mark_confirmed_booking_payment_as_paid(): void
    {
        $booking = $this->makeBooking('confirmed', 'unpaid');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/bookings/{$booking->id}/payment", ['status' => 'paid']);

        $response->assertRedirect(route('admin.bookings.show', $booking->id));
        $payment = $booking->fresh()->payment;
        $this->assertEquals('paid', $payment->status->value);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_admin_can_mark_pending_payment_status_as_paid_when_booking_confirmed(): void
    {
        $booking = $this->makeBooking('confirmed', 'pending');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/payments/{$booking->payment->id}/status", ['status' => 'paid']);

        $response->assertRedirect(route('admin.payments.index'));
        $this->assertEquals('paid', $booking->fresh()->payment->status->value);
    }

    public function test_admin_can_refund_paid_payment(): void
    {
        $booking = $this->makeBooking('confirmed', 'paid');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/payments/{$booking->payment->id}/status", ['status' => 'refunded']);

        $response->assertRedirect(route('admin.payments.index'));
        $this->assertEquals('refunded', $booking->fresh()->payment->status->value);
    }

    public function test_payment_status_update_logs_actor_and_timestamp(): void
    {
        $booking = $this->makeBooking('confirmed', 'unpaid');
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)->patch("/admin/bookings/{$booking->id}/payment", ['status' => 'paid']);

        $log = $booking->fresh()->payment->statusLogs->last();
        $this->assertEquals($admin->id, $log->changed_by);
        $this->assertEquals('unpaid', $log->from_status->value);
        $this->assertEquals('paid', $log->to_status->value);
        $this->assertNotNull($log->created_at);
    }

    public function test_admin_cannot_mark_paid_when_booking_not_confirmed(): void
    {
        foreach (['pending', 'cancelled', 'completed'] as $status) {
            $booking = $this->makeBooking($status, 'unpaid');

            $response = $this->actingAsAdmin($this->makeUser('admin'))
                ->patch("/admin/bookings/{$booking->id}/payment", ['status' => 'paid']);

            $response->assertSessionHasErrors('status');
            $this->assertEquals('unpaid', $booking->fresh()->payment->status->value);
        }
    }

    public static function invalidPaymentTransitionProvider(): array
    {
        return [
            'unpaid -> refunded'   => ['unpaid', 'refunded'],
            'pending -> refunded'  => ['pending', 'refunded'],
            'paid -> paid'         => ['paid', 'paid'],
            'refunded -> paid'     => ['refunded', 'paid'],
            'refunded -> refunded' => ['refunded', 'refunded'],
            'failed -> paid'       => ['failed', 'paid'],
        ];
    }

    #[DataProvider('invalidPaymentTransitionProvider')]
    public function test_invalid_payment_transition_is_blocked(string $initialPaymentStatus, string $targetStatus): void
    {
        // Dùng booking confirmed để cô lập test này với rule "chỉ confirmed mới paid".
        $booking = $this->makeBooking('confirmed', $initialPaymentStatus);

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/payments/{$booking->payment->id}/status", ['status' => $targetStatus]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals($initialPaymentStatus, $booking->fresh()->payment->status->value);
    }

    public function test_payment_status_rejects_value_outside_allowed_enum(): void
    {
        $booking = $this->makeBooking('confirmed', 'unpaid');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/payments/{$booking->payment->id}/status", ['status' => 'unpaid']);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('unpaid', $booking->fresh()->payment->status->value);
    }

    public function test_updating_payment_status_for_booking_without_payment_record_fails_clearly(): void
    {
        $booking = $this->makeBooking('confirmed', null);
        $this->assertNull($booking->payment);

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->patch("/admin/bookings/{$booking->id}/payment", ['status' => 'paid']);

        $response->assertSessionHasErrors('status');
    }

    // ------------------------------------------------------------
    // 4. FILTER / LIST
    // ------------------------------------------------------------

    public function test_bookings_list_filters_by_status(): void
    {
        $this->makeBooking('pending');
        $confirmed = $this->makeBooking('confirmed');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->get('/admin/bookings?status=confirmed');

        $response->assertOk();
        $response->assertSee($confirmed->booking_code);
    }

    public function test_bookings_list_filters_by_created_date_range(): void
    {
        $inRange = $this->makeBooking('pending');
        $outOfRange = $this->makeBooking('pending');
        $outOfRange->forceFill(['created_at' => now()->subDays(30)])->save();

        $response = $this->actingAsAdmin($this->makeUser('admin'))->get('/admin/bookings?' . http_build_query([
            'created_from' => now()->subDays(1)->format('Y-m-d'),
            'created_to'   => now()->addDays(1)->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee($inRange->booking_code);
        $response->assertDontSee($outOfRange->booking_code);
    }

    public function test_bookings_list_filters_by_check_in_date_range(): void
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

    public function test_bookings_list_filters_by_customer_name(): void
    {
        $match = $this->makeBooking('pending', customerName: 'Nguyễn Văn A');
        $other = $this->makeBooking('pending', customerName: 'Trần Thị B');

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->get('/admin/bookings?customer_name=' . urlencode('Nguyễn'));

        $response->assertOk();
        $response->assertSee($match->booking_code);
        $response->assertDontSee($other->booking_code);
    }

    public function test_bookings_list_filters_by_room_type(): void
    {
        $roomTypeA = RoomType::factory()->create(['name' => 'Phòng Deluxe Test']);
        $roomTypeB = RoomType::factory()->create(['name' => 'Phòng Suite Test']);

        $bookingA = $this->makeBooking('pending', roomType: $roomTypeA);
        $bookingB = $this->makeBooking('pending', roomType: $roomTypeB);

        $response = $this->actingAsAdmin($this->makeUser('admin'))
            ->get("/admin/bookings?room_type_id={$roomTypeA->id}");

        $response->assertOk();
        $response->assertSee($bookingA->booking_code);
        $response->assertDontSee($bookingB->booking_code);
    }

    public function test_payments_list_filters_by_status_booking_code_and_customer_name(): void
    {
        $unpaid = $this->makeBooking('confirmed', 'unpaid', 'Nguyễn Văn A');
        $paid = $this->makeBooking('confirmed', 'paid', 'Trần Thị B');

        $byStatus = $this->actingAsAdmin($this->makeUser('admin'))->get('/admin/payments?status=paid');
        $byStatus->assertOk();
        $byStatus->assertSee($paid->booking_code);
        $byStatus->assertDontSee($unpaid->booking_code);

        $byCode = $this->actingAsAdmin($this->makeUser('admin'))
            ->get("/admin/payments?booking_code={$unpaid->booking_code}");
        $byCode->assertOk();
        $byCode->assertSee($unpaid->booking_code);
        $byCode->assertDontSee($paid->booking_code);

        $byName = $this->actingAsAdmin($this->makeUser('admin'))
            ->get('/admin/payments?customer_name=' . urlencode('Nguyễn'));
        $byName->assertOk();
        $byName->assertSee('Nguyễn Văn A');
        $byName->assertDontSee('Trần Thị B');
    }
}
