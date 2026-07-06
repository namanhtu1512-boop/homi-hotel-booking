<?php

namespace Tests\Feature;

use App\Models\RoomType;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test trang chủ public — giới thiệu khách sạn + phòng nổi bật.
 */
class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_hotel_info_and_featured_rooms(): void
    {
        $this->seed(\Database\Seeders\HotelInfoSeeder::class);
        RoomType::factory()->create(['name' => 'Phòng Trải Nghiệm', 'price_per_night' => 800000]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Homi Hotel');
        $response->assertSee('Chọn loại phòng phù hợp');
        $response->assertSee('Phòng Trải Nghiệm');
        $response->assertSee('Tìm phòng');
    }

    public function test_home_page_hides_featured_rooms_section_when_no_active_room_types(): void
    {
        $this->seed(\Database\Seeders\HotelInfoSeeder::class);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Phòng nổi bật');
    }

    public function test_home_page_shows_map_embed_using_hotel_address(): void
    {
        $this->seed(\Database\Seeders\HotelInfoSeeder::class);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('google.com/maps', false);
        $response->assertSee('Chỉ đường');
    }
}
