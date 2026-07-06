<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test API JSON /api/v1/bookings* — trước đây 7/9 method của
 * Api\BookingController là stub trả "Chức năng đang phát triển", đã hoàn
 * thiện để gọi lại đúng BookingService (đã test kỹ ở luồng Blade). File này
 * là test đầu tiên cho toàn bộ controller, kể cả `store`/`checkAvailability`
 * vốn đã hoạt động từ trước nhưng chưa từng được test.
 *
 * Test case ID | Chức năng                                    | Kết quả mong đợi
 * TC-BAPI-001  | Kiểm tra phòng trống (public)                  | 200, có available_quantity/can_book
 * TC-BAPI-002  | Customer tạo đơn qua API                       | 201, có booking_code, items, payment
 * TC-BAPI-003  | Tạo đơn thiếu dữ liệu bắt buộc                 | 422
 * TC-BAPI-004  | Customer xem danh sách đơn của mình             | 200, chỉ thấy đơn của mình
 * TC-BAPI-005  | Lọc danh sách đơn theo status                  | Chỉ trả đơn đúng trạng thái
 * TC-BAPI-006  | Customer xem chi tiết đơn của mình              | 200, có breakdown items
 * TC-BAPI-007  | Customer không xem được đơn người khác          | 403
 * TC-BAPI-008  | Customer hủy đơn pending hợp lệ                | 200, status = cancelled
 * TC-BAPI-009  | Customer không hủy được đơn đã cancelled       | 422
 * TC-BAPI-010  | Guest không gọi được route customer            | 401
 * TC-BAPI-011  | Admin xem danh sách tất cả đơn                 | 200
 * TC-BAPI-012  | Admin lọc danh sách đơn theo status            | Chỉ trả đơn đúng trạng thái
 * TC-BAPI-013  | Admin xem chi tiết 1 đơn bất kỳ                | 200
 * TC-BAPI-014  | Admin xác nhận đơn pending                     | 200, status = confirmed
 * TC-BAPI-015  | Admin xác nhận đơn đã confirmed (sai transition)| 422
 * TC-BAPI-016  | Admin gửi status không hợp lệ                  | 422
 * TC-BAPI-017  | Admin đánh dấu đã thanh toán                   | 200, payment.status = paid
 * TC-BAPI-018  | Admin refund trực tiếp từ unpaid (sai transition)| 422
 * TC-BAPI-019  | Customer không gọi được route admin            | 403
 */
class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function makeBooking(
        User $customer,
        string $status = 'pending',
        ?RoomType $roomType = null,
    ): Booking {
        $roomType ??= RoomType::factory()->create(['price_per_night' => 1000000]);
        $checkIn = now()->addDays(5)->format('Y-m-d');

        $booking = Booking::create([
            'booking_code'   => 'API-TEST-' . uniqid(),
            'user_id'        => $customer->id,
            'check_in'       => $checkIn,
            'check_out'      => date('Y-m-d', strtotime($checkIn . ' +2 days')),
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
            'status'     => 'unpaid',
        ]);

        return $booking;
    }

    // ----------------------------------------------------------------
    // PUBLIC
    // ----------------------------------------------------------------

    public function test_check_availability_returns_quantity_and_can_book(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);

        $response = $this->getJson("/api/v1/room-types/{$roomType->id}/availability?check_in=2026-09-01&check_out=2026-09-03");

        $response->assertOk();
        $response->assertJsonPath('data.available_quantity', 5);
        $response->assertJsonPath('data.can_book', true);
    }

    // ----------------------------------------------------------------
    // CUSTOMER — store/cancel/index/show
    // ----------------------------------------------------------------

    public function test_customer_can_create_booking_via_api(): void
    {
        $customer = $this->makeUser('customer');
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000, 'total_rooms' => 5, 'capacity' => 2]);

        $response = $this->actingAs($customer)->postJson('/api/v1/bookings', [
            'items' => [
                ['room_type_id' => $roomType->id, 'quantity' => 1, 'adults' => 2],
            ],
            'check_in'       => '2026-09-10',
            'check_out'      => '2026-09-12',
            'customer_name'  => $customer->name,
            'customer_phone' => '0900000000',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'pending');
        $response->assertJsonPath('data.items.0.room_type_id', $roomType->id);
        $response->assertJsonPath('data.payment.status', 'unpaid');
        $this->assertDatabaseHas('bookings', ['customer_phone' => '0900000000']);
    }

    public function test_create_booking_fails_with_missing_fields(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)
            ->postJson('/api/v1/bookings', [])
            ->assertStatus(422);
    }

    public function test_customer_can_list_own_bookings(): void
    {
        $customer = $this->makeUser('customer');
        $other    = $this->makeUser('customer');
        $this->makeBooking($customer);
        $this->makeBooking($other);

        $response = $this->actingAs($customer)->getJson('/api/v1/bookings');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.data');
    }

    public function test_customer_can_filter_own_bookings_by_status(): void
    {
        $customer = $this->makeUser('customer');
        $this->makeBooking($customer, 'pending');
        $this->makeBooking($customer, 'cancelled');

        $response = $this->actingAs($customer)->getJson('/api/v1/bookings?status=cancelled');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.data');
        $response->assertJsonPath('data.data.0.status', 'cancelled');
    }

    public function test_customer_can_view_own_booking_detail_with_items(): void
    {
        $customer = $this->makeUser('customer');
        $booking  = $this->makeBooking($customer);

        $response = $this->actingAs($customer)->getJson("/api/v1/bookings/{$booking->id}");

        $response->assertOk();
        $response->assertJsonPath('data.booking_code', $booking->booking_code);
        $response->assertJsonCount(1, 'data.items');
    }

    public function test_customer_cannot_view_another_customers_booking(): void
    {
        $owner    = $this->makeUser('customer');
        $intruder = $this->makeUser('customer');
        $booking  = $this->makeBooking($owner);

        $this->actingAs($intruder)
            ->getJson("/api/v1/bookings/{$booking->id}")
            ->assertForbidden();
    }

    public function test_customer_can_cancel_pending_booking(): void
    {
        $customer = $this->makeUser('customer');
        $booking  = $this->makeBooking($customer, 'pending');

        $response = $this->actingAs($customer)->postJson("/api/v1/bookings/{$booking->id}/cancel");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'cancelled');
    }

    public function test_customer_cannot_cancel_already_cancelled_booking(): void
    {
        $customer = $this->makeUser('customer');
        $booking  = $this->makeBooking($customer, 'cancelled');

        $this->actingAs($customer)
            ->postJson("/api/v1/bookings/{$booking->id}/cancel")
            ->assertStatus(422);
    }

    public function test_guest_cannot_list_bookings(): void
    {
        $this->getJson('/api/v1/bookings')->assertUnauthorized();
    }

    // ----------------------------------------------------------------
    // ADMIN / STAFF
    // ----------------------------------------------------------------

    public function test_admin_can_list_all_bookings(): void
    {
        $admin    = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $this->makeBooking($customer);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/bookings')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    public function test_admin_can_filter_bookings_by_status(): void
    {
        $admin    = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $this->makeBooking($customer, 'pending');
        $this->makeBooking($customer, 'confirmed');

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/bookings?status=confirmed');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.data');
    }

    public function test_admin_can_view_booking_detail(): void
    {
        $admin    = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $booking  = $this->makeBooking($customer);

        $this->actingAs($admin)
            ->getJson("/api/v1/admin/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('data.booking_code', $booking->booking_code);
    }

    public function test_admin_can_confirm_pending_booking(): void
    {
        $admin    = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $booking  = $this->makeBooking($customer, 'pending');

        $response = $this->actingAs($admin)->putJson("/api/v1/admin/bookings/{$booking->id}/status", [
            'status' => 'confirmed',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'confirmed');
    }

    public function test_admin_cannot_confirm_already_confirmed_booking(): void
    {
        $admin    = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $booking  = $this->makeBooking($customer, 'confirmed');

        $this->actingAs($admin)
            ->putJson("/api/v1/admin/bookings/{$booking->id}/status", ['status' => 'confirmed'])
            ->assertStatus(422);
    }

    public function test_admin_status_update_rejects_invalid_status_value(): void
    {
        $admin    = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $booking  = $this->makeBooking($customer, 'pending');

        $this->actingAs($admin)
            ->putJson("/api/v1/admin/bookings/{$booking->id}/status", ['status' => 'pending'])
            ->assertStatus(422);
    }

    public function test_admin_can_mark_payment_as_paid(): void
    {
        $admin    = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $booking  = $this->makeBooking($customer, 'confirmed');

        $response = $this->actingAs($admin)->putJson("/api/v1/admin/bookings/{$booking->id}/payment", [
            'status' => 'paid',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.payment.status', 'paid');
    }

    public function test_admin_cannot_refund_directly_from_unpaid(): void
    {
        $admin    = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $booking  = $this->makeBooking($customer, 'confirmed');

        $this->actingAs($admin)
            ->putJson("/api/v1/admin/bookings/{$booking->id}/payment", ['status' => 'refunded'])
            ->assertStatus(422);
    }

    public function test_customer_cannot_access_admin_bookings_list(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)
            ->getJson('/api/v1/admin/bookings')
            ->assertForbidden();
    }
}
