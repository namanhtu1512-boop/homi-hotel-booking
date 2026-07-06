<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Check-in/check-out thật (gán số phòng vật lý cụ thể) + housekeeping tự
 * động chuyển "dirty" sau check-out — xem BookingService::checkIn()/checkOut().
 */
class CheckInCheckOutTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function makeBooking(string $status = 'confirmed', int $quantity = 1): array
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
            'total_amount'   => 2000000 * $quantity,
            'status'         => $status,
        ]);

        $item = BookingItem::create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomType->id,
            'quantity'        => $quantity,
            'price_per_night' => $roomType->price_per_night,
            'nights'          => 2,
            'subtotal'        => 2000000 * $quantity,
        ]);

        return [$booking, $item, $roomType];
    }

    public function test_admin_can_check_in_with_room_assignment(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $item, $roomType] = $this->makeBooking();
        $room = Room::factory()->create(['room_type_id' => $roomType->id, 'room_number' => '101']);

        $response = $this->actingAsAdmin($admin)->post("/admin/bookings/{$booking->id}/check-in", [
            'rooms' => [$item->id => [$room->id]],
        ]);

        $response->assertRedirect(route('admin.bookings.show', $booking->id));
        $this->assertSame('checked_in', $booking->fresh()->status->value);
        $this->assertDatabaseHas('booking_item_rooms', ['booking_item_id' => $item->id, 'room_id' => $room->id]);
    }

    public function test_check_in_fails_when_room_count_does_not_match_quantity(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $item, $roomType] = $this->makeBooking(quantity: 2);
        $room = Room::factory()->create(['room_type_id' => $roomType->id]);

        $response = $this->actingAsAdmin($admin)->post("/admin/bookings/{$booking->id}/check-in", [
            'rooms' => [$item->id => [$room->id]],
        ]);

        $response->assertSessionHasErrors('rooms');
        $this->assertSame('confirmed', $booking->fresh()->status->value);
    }

    public function test_check_in_fails_when_room_already_occupied(): void
    {
        $admin = $this->makeUser('admin');
        [$firstBooking, $firstItem, $roomType] = $this->makeBooking();
        $room = Room::factory()->create(['room_type_id' => $roomType->id]);

        // Phòng đã gán + check-in cho đơn khác trước đó.
        $this->actingAsAdmin($admin)->post("/admin/bookings/{$firstBooking->id}/check-in", [
            'rooms' => [$firstItem->id => [$room->id]],
        ]);

        [$secondBooking, $secondItem] = $this->makeBooking();
        BookingItem::where('id', $secondItem->id)->update(['room_type_id' => $roomType->id]);

        $response = $this->actingAsAdmin($admin)->post("/admin/bookings/{$secondBooking->id}/check-in", [
            'rooms' => [$secondItem->id => [$room->id]],
        ]);

        $response->assertSessionHasErrors('rooms');
        $this->assertSame('confirmed', $secondBooking->fresh()->status->value);
    }

    public function test_check_in_not_allowed_before_confirmed(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $item, $roomType] = $this->makeBooking(status: 'pending');
        $room = Room::factory()->create(['room_type_id' => $roomType->id]);

        $response = $this->actingAsAdmin($admin)->post("/admin/bookings/{$booking->id}/check-in", [
            'rooms' => [$item->id => [$room->id]],
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame('pending', $booking->fresh()->status->value);
    }

    public function test_admin_can_check_out_and_rooms_become_dirty(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $item, $roomType] = $this->makeBooking();
        $room = Room::factory()->create(['room_type_id' => $roomType->id, 'housekeeping_status' => 'clean']);

        $this->actingAsAdmin($admin)->post("/admin/bookings/{$booking->id}/check-in", [
            'rooms' => [$item->id => [$room->id]],
        ]);

        $response = $this->actingAsAdmin($admin)->post("/admin/bookings/{$booking->id}/check-out");

        $response->assertRedirect(route('admin.bookings.show', $booking->id));
        $this->assertSame('checked_out', $booking->fresh()->status->value);
        $this->assertSame('dirty', $room->fresh()->housekeeping_status);
    }

    public function test_check_out_not_allowed_before_check_in(): void
    {
        $admin = $this->makeUser('admin');
        [$booking] = $this->makeBooking();

        $response = $this->actingAsAdmin($admin)->post("/admin/bookings/{$booking->id}/check-out");

        $response->assertSessionHasErrors('status');
        $this->assertSame('confirmed', $booking->fresh()->status->value);
    }

    public function test_admin_can_complete_booking_after_check_out(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $item, $roomType] = $this->makeBooking();
        $room = Room::factory()->create(['room_type_id' => $roomType->id]);

        $this->actingAsAdmin($admin)->post("/admin/bookings/{$booking->id}/check-in", [
            'rooms' => [$item->id => [$room->id]],
        ]);
        $this->actingAsAdmin($admin)->post("/admin/bookings/{$booking->id}/check-out");

        $response = $this->actingAsAdmin($admin)->post("/admin/bookings/{$booking->id}/complete");

        $response->assertRedirect(route('admin.bookings.show', $booking->id));
        $this->assertSame('completed', $booking->fresh()->status->value);
    }

    public function test_staff_can_check_in_and_check_out_too(): void
    {
        $staff = $this->makeUser('staff');
        [$booking, $item, $roomType] = $this->makeBooking();
        $room = Room::factory()->create(['room_type_id' => $roomType->id]);

        $this->actingAsAdmin($staff)->post("/staff/bookings/{$booking->id}/check-in", [
            'rooms' => [$item->id => [$room->id]],
        ])->assertRedirect(route('staff.bookings.show', $booking->id));

        $this->assertSame('checked_in', $booking->fresh()->status->value);

        $this->actingAsAdmin($staff)
            ->post("/staff/bookings/{$booking->id}/check-out")
            ->assertRedirect(route('staff.bookings.show', $booking->id));

        $this->assertSame('checked_out', $booking->fresh()->status->value);
    }

    public function test_customer_cannot_check_in_booking(): void
    {
        $customer = $this->makeUser('customer');
        [$booking, $item, $roomType] = $this->makeBooking();
        $room = Room::factory()->create(['room_type_id' => $roomType->id]);

        $this->actingAs($customer)
            ->post("/admin/bookings/{$booking->id}/check-in", ['rooms' => [$item->id => [$room->id]]])
            ->assertRedirect(route('customer.dashboard'));

        $this->assertSame('confirmed', $booking->fresh()->status->value);
    }
}
