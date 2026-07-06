<?php

namespace Tests\Feature\Booking;

use App\Models\RoomType;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dịch vụ thêm gắn vào đơn đặt phòng (booking_services) — xem
 * BookingService::create(), Service/BookingServiceItem.
 */
class BookingServicesTest extends TestCase
{
    use RefreshDatabase;

    private function bookingPayload(RoomType $roomType, array $overrides = []): array
    {
        return array_merge([
            'items'          => [['room_type_id' => $roomType->id, 'quantity' => 1, 'adults' => 1, 'children' => 0]],
            'check_in'       => now()->addDays(5)->format('Y-m-d'),
            'check_out'      => now()->addDays(6)->format('Y-m-d'),
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
        ], $overrides);
    }

    public function test_booking_with_one_service_attaches_item_with_price_snapshot(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $service  = Service::factory()->create(['price' => 150000]);

        $response = $this->actingAs($customer)->post('/customer/bookings', $this->bookingPayload($roomType, [
            'services' => [['service_id' => $service->id, 'quantity' => 2]],
        ]));

        $booking = $customer->bookings()->first();
        $response->assertRedirect(route('customer.bookings.show', $booking->id));

        $this->assertCount(1, $booking->serviceItems);
        $item = $booking->serviceItems->first();
        $this->assertEquals($service->id, $item->service_id);
        $this->assertEquals(2, $item->quantity);
        $this->assertEquals(150000, $item->unit_price);
        $this->assertEquals(300000, $item->subtotal);

        // Tổng đơn = tiền phòng (1.000.000) + dịch vụ (150.000 x 2 = 300.000).
        $this->assertEquals(1300000, $booking->total_amount);
    }

    public function test_booking_with_multiple_services(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $breakfast = Service::factory()->create(['price' => 100000]);
        $pickup    = Service::factory()->create(['price' => 300000]);

        $this->actingAs($customer)->post('/customer/bookings', $this->bookingPayload($roomType, [
            'services' => [
                ['service_id' => $breakfast->id, 'quantity' => 2],
                ['service_id' => $pickup->id, 'quantity' => 1],
            ],
        ]));

        $booking = $customer->bookings()->first();

        $this->assertCount(2, $booking->serviceItems);
        // 1.000.000 phòng + (100.000x2) + 300.000 = 1.500.000
        $this->assertEquals(1500000, $booking->total_amount);
    }

    public function test_price_snapshot_is_unaffected_by_later_service_price_change(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $service  = Service::factory()->create(['price' => 150000]);

        $this->actingAs($customer)->post('/customer/bookings', $this->bookingPayload($roomType, [
            'services' => [['service_id' => $service->id, 'quantity' => 1]],
        ]));

        $booking = $customer->bookings()->first();

        $service->update(['price' => 999999]);

        $this->assertEquals(150000, $booking->serviceItems->first()->unit_price);
    }

    public function test_hidden_service_id_is_rejected(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        $hidden   = Service::factory()->hidden()->create();

        $response = $this->actingAs($customer)->post('/customer/bookings', $this->bookingPayload($roomType, [
            'services' => [['service_id' => $hidden->id, 'quantity' => 1]],
        ]));

        $response->assertNotFound();
        $this->assertCount(0, $customer->bookings);
    }

    public function test_booking_without_any_service_still_works(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);

        $response = $this->actingAs($customer)->post('/customer/bookings', $this->bookingPayload($roomType));

        $booking = $customer->bookings()->first();
        $response->assertRedirect(route('customer.bookings.show', $booking->id));
        $this->assertCount(0, $booking->serviceItems);
        $this->assertEquals(1000000, $booking->total_amount);
    }
}
