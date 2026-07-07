<?php

namespace Tests\Feature\Review;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US11 (Should) — Customer đánh giá phòng: chỉ đơn confirmed/completed mới
 * được đánh giá, mỗi đơn+loại phòng chỉ đánh giá 1 lần.
 */
class CustomerReviewTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(User $customer, RoomType $roomType, string $status): Booking
    {
        $booking = Booking::create([
            'user_id'        => $customer->id,
            'booking_code'   => 'TEST-' . uniqid(),
            'check_in'       => now()->subDays(5),
            'check_out'      => now()->subDays(3),
            'nights'         => 2,
            'customer_name'  => $customer->name,
            'customer_phone' => '0900000000',
            'total_amount'   => $roomType->price_per_night * 2,
            'status'         => $status,
        ]);

        BookingItem::create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomType->id,
            'quantity'        => 1,
            'price_per_night' => $roomType->price_per_night,
            'nights'          => 2,
            'subtotal'        => $roomType->price_per_night * 2,
        ]);

        return $booking;
    }

    public function test_customer_can_review_confirmed_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        $booking  = $this->makeBooking($customer, $roomType, 'confirmed');

        $this->actingAs($customer)
            ->post(route('customer.reviews.store'), [
                'booking_id'   => $booking->id,
                'room_type_id' => $roomType->id,
                'rating'       => 5,
                'comment'      => 'Phòng rất đẹp!',
            ])
            ->assertRedirect(route('customer.bookings.index'));

        $this->assertDatabaseHas('reviews', [
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'rating'       => 5,
        ]);
    }

    public function test_customer_can_review_completed_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        $booking  = $this->makeBooking($customer, $roomType, 'completed');

        $this->actingAs($customer)
            ->post(route('customer.reviews.store'), [
                'booking_id'   => $booking->id,
                'room_type_id' => $roomType->id,
                'rating'       => 4,
            ])
            ->assertRedirect(route('customer.bookings.index'));

        $this->assertDatabaseHas('reviews', ['booking_id' => $booking->id, 'rating' => 4]);
    }

    public function test_customer_cannot_review_pending_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        $booking  = $this->makeBooking($customer, $roomType, 'pending');

        $this->actingAs($customer)
            ->post(route('customer.reviews.store'), [
                'booking_id'   => $booking->id,
                'room_type_id' => $roomType->id,
                'rating'       => 5,
            ])
            ->assertSessionHasErrors(['booking_id']);

        $this->assertDatabaseMissing('reviews', ['booking_id' => $booking->id]);
    }

    public function test_customer_cannot_review_same_booking_and_room_type_twice(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        $booking  = $this->makeBooking($customer, $roomType, 'completed');

        $this->actingAs($customer)->post(route('customer.reviews.store'), [
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'rating'       => 5,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.reviews.store'), [
                'booking_id'   => $booking->id,
                'room_type_id' => $roomType->id,
                'rating'       => 3,
            ])
            ->assertSessionHasErrors(['room_type_id']);

        $this->assertSame(1, \App\Models\Review::where('booking_id', $booking->id)->count());
    }

    public function test_customer_cannot_review_another_customers_booking(): void
    {
        $owner    = User::factory()->customer()->create();
        $intruder = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        $booking  = $this->makeBooking($owner, $roomType, 'completed');

        $this->actingAs($intruder)
            ->post(route('customer.reviews.store'), [
                'booking_id'   => $booking->id,
                'room_type_id' => $roomType->id,
                'rating'       => 5,
            ])
            ->assertNotFound();
    }

    public function test_reviewable_items_lists_confirmed_and_completed_bookings_not_yet_reviewed(): void
    {
        $customer  = User::factory()->customer()->create();
        $roomType1 = RoomType::factory()->create();
        $roomType2 = RoomType::factory()->create();
        $this->makeBooking($customer, $roomType1, 'confirmed');
        $this->makeBooking($customer, $roomType2, 'pending');

        $response = $this->actingAs($customer)->get(route('customer.reviews.create'));

        $response->assertOk();
        $response->assertViewHas('items', fn ($items) => $items->count() === 1);
    }
}
