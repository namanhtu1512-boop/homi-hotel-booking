<?php

namespace Tests\Feature\Booking;

use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 4 - Tuần 8 (BE4): Test API kiểm tra phòng trống dùng bởi form
 * "Kiểm tra phòng trống" trên trang Room Detail.
 * Route: GET /api/v1/room-types/{roomType}/availability (public, không cần đăng nhập).
 *
 * Bổ sung coverage cho phòng không tồn tại / inactive khi gọi thẳng API,
 * vì trang Web (/rooms/{id}) đã 404 trước khi tới bước này, còn API thì
 * client có thể gọi thẳng endpoint availability với id bất kỳ.
 */
class AvailabilityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_availability_check_returns_data_for_active_room(): void
    {
        $room = RoomType::factory()->create(['total_rooms' => 5]);
        $checkIn  = now()->addDays(3)->format('Y-m-d');
        $checkOut = now()->addDays(5)->format('Y-m-d');

        $response = $this->getJson("/api/v1/room-types/{$room->id}/availability?check_in={$checkIn}&check_out={$checkOut}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.can_book', true)
            ->assertJsonPath('data.available_quantity', 5);
    }

    public function test_availability_check_for_nonexistent_room_returns_404(): void
    {
        $checkIn  = now()->addDays(3)->format('Y-m-d');
        $checkOut = now()->addDays(5)->format('Y-m-d');

        $this->getJson("/api/v1/room-types/999999/availability?check_in={$checkIn}&check_out={$checkOut}")
            ->assertNotFound();
    }

    public function test_availability_check_for_hidden_room_returns_404(): void
    {
        $room = RoomType::factory()->hidden()->create();
        $checkIn  = now()->addDays(3)->format('Y-m-d');
        $checkOut = now()->addDays(5)->format('Y-m-d');

        $this->getJson("/api/v1/room-types/{$room->id}/availability?check_in={$checkIn}&check_out={$checkOut}")
            ->assertNotFound();
    }

    public function test_availability_check_for_maintenance_room_returns_404(): void
    {
        $room = RoomType::factory()->maintenance()->create();
        $checkIn  = now()->addDays(3)->format('Y-m-d');
        $checkOut = now()->addDays(5)->format('Y-m-d');

        $this->getJson("/api/v1/room-types/{$room->id}/availability?check_in={$checkIn}&check_out={$checkOut}")
            ->assertNotFound();
    }

    public function test_availability_check_missing_check_in_returns_422(): void
    {
        $room = RoomType::factory()->create();

        $this->getJson("/api/v1/room-types/{$room->id}/availability?check_out=" . now()->addDays(5)->format('Y-m-d'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_in']);
    }

    public function test_availability_check_missing_check_out_returns_422(): void
    {
        $room = RoomType::factory()->create();

        $this->getJson("/api/v1/room-types/{$room->id}/availability?check_in=" . now()->addDays(3)->format('Y-m-d'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_out']);
    }

    public function test_availability_check_quantity_over_max_returns_422(): void
    {
        $room = RoomType::factory()->create();
        $checkIn  = now()->addDays(3)->format('Y-m-d');
        $checkOut = now()->addDays(5)->format('Y-m-d');

        $this->getJson("/api/v1/room-types/{$room->id}/availability?check_in={$checkIn}&check_out={$checkOut}&quantity=11")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_availability_check_reflects_existing_bookings(): void
    {
        $room = RoomType::factory()->create(['total_rooms' => 2]);
        $customer = \App\Models\User::factory()->customer()->create();
        $checkIn  = now()->addDays(3)->format('Y-m-d');
        $checkOut = now()->addDays(5)->format('Y-m-d');

        $this->actingAs($customer)->post('/customer/bookings', [
            'room_type_id'   => $room->id,
            'check_in'       => $checkIn,
            'check_out'      => $checkOut,
            'quantity'       => 2,
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
        ]);

        $response = $this->getJson("/api/v1/room-types/{$room->id}/availability?check_in={$checkIn}&check_out={$checkOut}");

        $response->assertOk()
            ->assertJsonPath('data.available_quantity', 0)
            ->assertJsonPath('data.can_book', false);
    }
}
