<?php

namespace Tests\Feature\Booking;

use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test luồng client: xem chi tiết phòng -> kiểm tra trống -> tạo đơn ->
 * xem/hủy đơn của tôi. Dùng lại BookingService/AvailabilityService đã có sẵn.
 */
class CustomerBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function validBookingPayload(RoomType $roomType, array $override = []): array
    {
        return array_merge([
            'items'          => [['room_type_id' => $roomType->id, 'quantity' => 1, 'adults' => 1, 'children' => 0]],
            'check_in'       => now()->addDays(5)->format('Y-m-d'),
            'check_out'      => now()->addDays(7)->format('Y-m-d'),
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
            'customer_email' => 'a@example.com',
        ], $override);
    }

    public function test_guest_can_view_room_detail_with_availability(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);

        $response = $this->get("/rooms/{$roomType->id}?check_in=" . now()->addDays(5)->format('Y-m-d') . '&check_out=' . now()->addDays(7)->format('Y-m-d'));

        $response->assertOk();
        $response->assertSee($roomType->name);
        $response->assertSee('Còn 5 phòng trống');
    }

    public function test_hidden_room_detail_returns_404(): void
    {
        $roomType = RoomType::factory()->hidden()->create();

        $this->get("/rooms/{$roomType->id}")->assertNotFound();
    }

    public function test_guest_is_redirected_to_login_when_creating_booking(): void
    {
        $this->get('/customer/bookings/create')->assertRedirect(route('login'));
    }

    public function test_customer_can_create_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['total_rooms' => 3, 'price_per_night' => 1000000]);

        $response = $this->actingAs($customer)
            ->post('/customer/bookings', $this->validBookingPayload($roomType));

        $booking = $customer->bookings()->first();

        $response->assertRedirect(route('customer.bookings.show', $booking->id));
        $this->assertNotNull($booking);
        $this->assertEquals('pending', $booking->status->value);
        $this->assertEquals(2000000, $booking->total_amount);
        $this->assertEquals('unpaid', $booking->payment->status->value);
    }

    public function test_customer_can_book_multiple_room_types_in_one_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $deluxe = RoomType::factory()->create(['total_rooms' => 3, 'price_per_night' => 1000000, 'capacity' => 2]);
        $suite  = RoomType::factory()->create(['total_rooms' => 3, 'price_per_night' => 2000000, 'capacity' => 4]);

        // Số khách khai báo riêng cho từng loại phòng: deluxe 2 người lớn
        // (đủ capacity 2×1), suite 1 người lớn + 1 trẻ em (đủ capacity 4×2).
        $response = $this->actingAs($customer)->post('/customer/bookings', $this->validBookingPayload($deluxe, [
            'items' => [
                ['room_type_id' => $deluxe->id, 'quantity' => 1, 'adults' => 2, 'children' => 0],
                ['room_type_id' => $suite->id, 'quantity' => 2, 'adults' => 1, 'children' => 1],
            ],
        ]));

        $booking = $customer->bookings()->first();

        $response->assertRedirect(route('customer.bookings.show', $booking->id));
        $this->assertCount(2, $booking->bookingItems);
        // 2 đêm: deluxe 1×1tr×2 + suite 2×2tr×2 = 2tr + 8tr = 10tr
        $this->assertEquals(10000000, $booking->total_amount);
        // Tổng số khách trên booking = tổng các dòng: (2+0) + (1+1) = 4.
        $this->assertEquals(3, $booking->adults);
        $this->assertEquals(1, $booking->children);

        $deluxeItem = $booking->bookingItems->firstWhere('room_type_id', $deluxe->id);
        $suiteItem  = $booking->bookingItems->firstWhere('room_type_id', $suite->id);
        $this->assertEquals(2, $deluxeItem->adults);
        $this->assertEquals(0, $deluxeItem->children);
        $this->assertEquals(1, $suiteItem->adults);
        $this->assertEquals(1, $suiteItem->children);
    }

    public function test_booking_fails_when_guests_exceed_capacity_of_a_room_type(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['total_rooms' => 5, 'capacity' => 2]);

        // 1 phòng sức chứa 2, nhưng khai báo 3 người lớn cho chính dòng đó → vượt sức chứa.
        $response = $this->actingAs($customer)->post('/customer/bookings', $this->validBookingPayload($roomType, [
            'items' => [['room_type_id' => $roomType->id, 'quantity' => 1, 'adults' => 3, 'children' => 0]],
        ]));

        $response->assertSessionHasErrors('items.0.adults');
        $this->assertCount(0, $customer->bookings);
    }

    public function test_booking_fails_when_one_room_type_exceeds_capacity_even_if_other_has_spare_capacity(): void
    {
        $customer = User::factory()->customer()->create();
        // Deluxe thừa sức chứa (2×5=10) nhưng standard bị vượt (1×1=1 < 2 khách yêu cầu).
        $deluxe   = RoomType::factory()->create(['total_rooms' => 5, 'capacity' => 5]);
        $standard = RoomType::factory()->create(['total_rooms' => 5, 'capacity' => 1]);

        $response = $this->actingAs($customer)->post('/customer/bookings', $this->validBookingPayload($deluxe, [
            'items' => [
                ['room_type_id' => $deluxe->id, 'quantity' => 1, 'adults' => 1, 'children' => 0],
                ['room_type_id' => $standard->id, 'quantity' => 1, 'adults' => 2, 'children' => 0],
            ],
        ]));

        $response->assertSessionHasErrors('items.1.adults');
        $this->assertCount(0, $customer->bookings);
    }

    public function test_booking_fails_when_not_enough_rooms_available(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['total_rooms' => 1]);

        $payload = $this->validBookingPayload($roomType, ['quantity' => 1]);

        // Phòng duy nhất đã có người đặt trước cho cùng khoảng ngày.
        $this->actingAs(User::factory()->customer()->create())->post('/customer/bookings', $payload);

        $response = $this->actingAs($customer)->post('/customer/bookings', $payload);

        $response->assertSessionHasErrors('items');
        $this->assertCount(0, $customer->bookings);
    }

    public function test_customer_can_view_own_bookings_list_and_detail(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();

        $this->actingAs($customer)->post('/customer/bookings', $this->validBookingPayload($roomType));
        $booking = $customer->bookings()->first();

        $this->actingAs($customer)
            ->get('/customer/bookings')
            ->assertOk()
            ->assertSee($booking->booking_code);

        $this->actingAs($customer)
            ->get("/customer/bookings/{$booking->id}")
            ->assertOk()
            ->assertSee($booking->booking_code);
    }

    public function test_customer_cannot_view_another_customers_booking(): void
    {
        $owner = User::factory()->customer()->create();
        $intruder = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();

        $this->actingAs($owner)->post('/customer/bookings', $this->validBookingPayload($roomType));
        $booking = $owner->bookings()->first();

        $this->actingAs($intruder)
            ->get("/customer/bookings/{$booking->id}")
            ->assertForbidden();
    }

    public function test_customer_can_cancel_pending_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();

        $this->actingAs($customer)->post('/customer/bookings', $this->validBookingPayload($roomType));
        $booking = $customer->bookings()->first();

        $response = $this->actingAs($customer)->post("/customer/bookings/{$booking->id}/cancel");

        $response->assertRedirect(route('customer.bookings.show', $booking->id));
        $this->assertEquals('cancelled', $booking->fresh()->status->value);
    }

    public function test_customer_cannot_cancel_booking_on_or_after_check_in_date(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();

        $this->actingAs($customer)->post('/customer/bookings', $this->validBookingPayload($roomType));
        $booking = $customer->bookings()->first();

        // Ngày nhận phòng đã tới/qua — không còn hủy trước check-in được nữa.
        $booking->update(['check_in' => today()]);

        $response = $this->actingAs($customer)->post("/customer/bookings/{$booking->id}/cancel");

        $response->assertSessionHasErrors('status');
        $this->assertEquals('pending', $booking->fresh()->status->value);
    }

    public function test_availability_is_restored_after_customer_cancels_booking(): void
    {
        $owner = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['total_rooms' => 1]);

        $payload = $this->validBookingPayload($roomType, ['quantity' => 1]);

        $this->actingAs($owner)->post('/customer/bookings', $payload);
        $booking = $owner->bookings()->first();

        // Phòng duy nhất đã bị giữ — khách khác không đặt được nữa.
        $blockedResponse = $this->actingAs($otherCustomer)->post('/customer/bookings', $payload);
        $blockedResponse->assertSessionHasErrors('items');
        $this->assertCount(0, $otherCustomer->bookings);

        $this->actingAs($owner)->post("/customer/bookings/{$booking->id}/cancel");
        $this->assertEquals('cancelled', $booking->fresh()->status->value);

        // Sau khi hủy, availability được tính lại đúng — khách khác đặt được.
        $response = $this->actingAs($otherCustomer)->post('/customer/bookings', $payload);
        $response->assertSessionDoesntHaveErrors();
        $this->assertCount(1, $otherCustomer->fresh()->bookings);
    }

    public function test_staff_and_admin_cannot_use_customer_booking_routes(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        $this->actingAs($customer)->post('/customer/bookings', $this->validBookingPayload($roomType));
        $booking = $customer->bookings()->first();

        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($staff)
            ->get('/customer/bookings')
            ->assertRedirect(route('staff.dashboard'));

        $this->actingAs($admin)
            ->get("/customer/bookings/{$booking->id}")
            ->assertRedirect(route('admin.dashboard'));

        $this->assertEquals('pending', $booking->fresh()->status->value);
    }
}
