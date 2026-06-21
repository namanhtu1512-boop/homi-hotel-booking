<?php

namespace Tests\Feature\RoomType;

use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tuần 7 - Sprint 4: Test trang public /rooms (danh sách + lọc phòng).
 */
class RoomListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_room_list(): void
    {
        RoomType::factory()->create(['name' => 'Phòng Standard']);

        $this->get('/rooms')
            ->assertOk()
            ->assertSee('Phòng Standard');
    }

    public function test_only_active_room_types_are_shown(): void
    {
        RoomType::factory()->create(['name' => 'Phòng Active']);
        RoomType::factory()->hidden()->create(['name' => 'Phòng Hidden']);
        RoomType::factory()->maintenance()->create(['name' => 'Phòng Maintenance']);

        $response = $this->get('/rooms');

        $response->assertSee('Phòng Active');
        $response->assertDontSee('Phòng Hidden');
        $response->assertDontSee('Phòng Maintenance');
    }

    public function test_keyword_filter_matches_name(): void
    {
        RoomType::factory()->create(['name' => 'Phòng Deluxe View Biển']);
        RoomType::factory()->create(['name' => 'Phòng Family']);

        $response = $this->get('/rooms?keyword=Deluxe');

        $response->assertSee('Phòng Deluxe View Biển');
        $response->assertDontSee('Phòng Family');
    }

    public function test_price_range_filter(): void
    {
        RoomType::factory()->create(['name' => 'Phòng Rẻ', 'price_per_night' => 500000]);
        RoomType::factory()->create(['name' => 'Phòng Đắt', 'price_per_night' => 5000000]);

        $response = $this->get('/rooms?min_price=1000000&max_price=6000000');

        $response->assertSee('Phòng Đắt');
        $response->assertDontSee('Phòng Rẻ');
    }

    public function test_capacity_filter_is_minimum(): void
    {
        RoomType::factory()->create(['name' => 'Phòng 2 Khách', 'capacity' => 2]);
        RoomType::factory()->create(['name' => 'Phòng 4 Khách', 'capacity' => 4]);

        $response = $this->get('/rooms?capacity=3');

        $response->assertSee('Phòng 4 Khách');
        $response->assertDontSee('Phòng 2 Khách');
    }

    public function test_invalid_price_range_shows_validation_error_instead_of_breaking_page(): void
    {
        $response = $this->from('/rooms')->get('/rooms?min_price=500&max_price=100');

        $response->assertRedirect('/rooms');
        $response->assertSessionHasErrors('max_price');
    }

    public function test_check_in_without_check_out_shows_validation_error(): void
    {
        $response = $this->from('/rooms')->get('/rooms?check_in=' . now()->addDay()->format('Y-m-d'));

        $response->assertSessionHasErrors('check_out');
    }
}
