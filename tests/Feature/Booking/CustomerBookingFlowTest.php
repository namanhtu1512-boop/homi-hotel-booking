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
            'room_type_id'   => $roomType->id,
            'check_in'       => now()->addDays(5)->format('Y-m-d'),
            'check_out'      => now()->addDays(7)->format('Y-m-d'),
            'quantity'       => 1,
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
        $this->get('/customer/booking/create')->assertRedirect(route('login'));
    }

    public function test_customer_can_create_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['total_rooms' => 3, 'price_per_night' => 1000000]);

        $response = $this->actingAs($customer)
            ->post('/customer/booking', $this->validBookingPayload($roomType));

        $booking = $customer->bookings()->first();

        $response->assertRedirect(route('customer.bookings.show', $booking->id));
        $this->assertNotNull($booking);
        $this->assertEquals('pending', $booking->status->value);
        $this->assertEquals(2000000, $booking->total_amount);
        $this->assertEquals('unpaid', $booking->payment->status->value);
    }

    public function test_booking_fails_when_not_enough_rooms_available(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['total_rooms' => 1]);

        $payload = $this->validBookingPayload($roomType, ['quantity' => 1]);

        // Phòng duy nhất đã có người đặt trước cho cùng khoảng ngày.
        $this->actingAs(User::factory()->customer()->create())->post('/customer/booking', $payload);

        $response = $this->actingAs($customer)->post('/customer/booking', $payload);

        $response->assertSessionHasErrors('room_type_id');
        $this->assertCount(0, $customer->bookings);
    }

    public function test_customer_can_view_own_bookings_list_and_detail(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();

        $this->actingAs($customer)->post('/customer/booking', $this->validBookingPayload($roomType));
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

        $this->actingAs($owner)->post('/customer/booking', $this->validBookingPayload($roomType));
        $booking = $owner->bookings()->first();

        $this->actingAs($intruder)
            ->get("/customer/bookings/{$booking->id}")
            ->assertForbidden();
    }

    public function test_customer_can_cancel_pending_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();

        $this->actingAs($customer)->post('/customer/booking', $this->validBookingPayload($roomType));
        $booking = $customer->bookings()->first();

        $response = $this->actingAs($customer)->post("/customer/bookings/{$booking->id}/cancel");

        $response->assertRedirect(route('customer.bookings.show', $booking->id));
        $this->assertEquals('cancelled', $booking->fresh()->status->value);
    }
}
