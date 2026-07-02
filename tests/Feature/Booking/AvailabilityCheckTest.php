<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tuần 9 - Sprint 5: Test tích hợp nút "Kiểm tra phòng trống" trên UI form
 * đặt phòng (/customer/bookings/create). Logic overlap đã được test riêng ở
 * AvailabilityServiceTest — file này chỉ test route/UI trả đúng dữ liệu.
 */
class AvailabilityCheckTest extends TestCase
{
    use RefreshDatabase;

    private function createBooking(RoomType $roomType, string $checkIn, string $checkOut, int $quantity, string $status = 'confirmed'): Booking
    {
        $booking = Booking::create([
            'booking_code'   => 'TEST-' . uniqid(),
            'check_in'       => $checkIn,
            'check_out'      => $checkOut,
            'nights'         => (new \DateTime($checkIn))->diff(new \DateTime($checkOut))->days,
            'customer_name'  => 'Khách Test',
            'customer_phone' => '0900000000',
            'total_amount'   => 0,
            'status'         => $status,
        ]);

        BookingItem::create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomType->id,
            'quantity'        => $quantity,
            'price_per_night' => $roomType->price_per_night,
            'nights'          => $booking->nights,
            'subtotal'        => 0,
        ]);

        return $booking;
    }

    public function test_booking_form_shows_check_availability_button(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();

        $response = $this->actingAs($customer)
            ->get("/customer/bookings/create?room_type_id={$roomType->id}");

        $response->assertOk();
        $response->assertSee('Kiểm tra phòng trống');
    }

    public function test_booking_form_shows_available_rooms_when_dates_are_free(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);

        $response = $this->actingAs($customer)->get(
            "/customer/bookings/create?room_type_id={$roomType->id}&check_in=2026-07-10&check_out=2026-07-12&quantity=1"
        );

        $response->assertOk();
        $response->assertSee('Còn 5 phòng trống');
    }

    public function test_booking_form_shows_sold_out_message_when_no_rooms_left(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['total_rooms' => 2]);
        $this->createBooking($roomType, '2026-07-10', '2026-07-12', 2);

        $response = $this->actingAs($customer)->get(
            "/customer/bookings/create?room_type_id={$roomType->id}&check_in=2026-07-10&check_out=2026-07-12&quantity=1"
        );

        $response->assertOk();
        $response->assertSee('Chỉ còn 0 phòng trống');
    }

    public function test_booking_form_shows_error_for_past_check_in_date(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();

        $response = $this->actingAs($customer)->get(
            "/customer/bookings/create?room_type_id={$roomType->id}&check_in=2020-01-01&check_out=2020-01-03&quantity=1"
        );

        $response->assertOk();
        $response->assertSee('Ngày nhận phòng không được trước hôm nay');
    }

    public function test_booking_form_without_dates_shows_no_availability_result(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();

        $response = $this->actingAs($customer)
            ->get("/customer/bookings/create?room_type_id={$roomType->id}");

        $response->assertOk();
        $response->assertDontSee('phòng trống cho');
    }

    public function test_overlapping_seed_style_bookings_reduce_availability_correctly(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['total_rooms' => 3]);

        // 3 booking giao nhau trong khoảng 21-22 (mô phỏng BookingSeeder Tuần 9)
        $this->createBooking($roomType, '2026-07-18', '2026-07-22', 1);
        $this->createBooking($roomType, '2026-07-20', '2026-07-25', 1);
        $this->createBooking($roomType, '2026-07-21', '2026-07-23', 1);

        $response = $this->actingAs($customer)->get(
            "/customer/bookings/create?room_type_id={$roomType->id}&check_in=2026-07-21&check_out=2026-07-22&quantity=1"
        );

        $response->assertOk();
        $response->assertSee('Chỉ còn 0 phòng trống');

        // Ngoài khoảng giao nhau vẫn còn phòng
        $response2 = $this->actingAs($customer)->get(
            "/customer/bookings/create?room_type_id={$roomType->id}&check_in=2026-07-14&check_out=2026-07-16&quantity=1"
        );

        $response2->assertOk();
        $response2->assertSee('Còn 3 phòng trống');
    }
}
