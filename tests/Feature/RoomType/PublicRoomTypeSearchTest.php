<?php

namespace Tests\Feature\RoomType;

use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE4 Tuần 7 — Test tìm kiếm / danh sách phòng công khai.
 * Route: GET /api/v1/room-types
 * Không cần đăng nhập. Chỉ trả về phòng active.
 *
 * Chạy: php artisan test --filter=PublicRoomTypeSearchTest
 */
class PublicRoomTypeSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeRoom(array $attrs = []): RoomType
    {
        return RoomType::factory()->create(array_merge(['status' => 'active'], $attrs));
    }

    // =========================================================
    // TC-PRS-001: Truy cập không cần đăng nhập
    // =========================================================

    public function test_TC_PRS_001_public_listing_requires_no_auth(): void
    {
        $this->makeRoom();

        $this->getJson('/api/v1/room-types')
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    // =========================================================
    // TC-PRS-002: Chỉ trả về phòng active
    // =========================================================

    public function test_TC_PRS_002_only_active_rooms_returned(): void
    {
        $active = $this->makeRoom(['status' => 'active']);
        $this->makeRoom(['status' => 'hidden']);
        $this->makeRoom(['status' => 'maintenance']);

        $response = $this->getJson('/api/v1/room-types');

        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertContains($active->id, $ids);
        $this->assertCount(1, $ids);
    }

    // =========================================================
    // TC-PRS-003: Phòng đã soft-delete không xuất hiện
    // =========================================================

    public function test_TC_PRS_003_soft_deleted_rooms_excluded(): void
    {
        $room = $this->makeRoom();
        $room->delete();

        $response = $this->getJson('/api/v1/room-types');

        $ids = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertNotContains($room->id, $ids);
    }

    // =========================================================
    // TC-PRS-010: Filter theo keyword
    // =========================================================

    public function test_TC_PRS_010_filter_by_keyword_matches_name(): void
    {
        $this->makeRoom(['name' => 'Phòng Suite Sang Trọng']);
        $this->makeRoom(['name' => 'Phòng Standard Cơ Bản']);

        $response = $this->getJson('/api/v1/room-types?keyword=Suite');

        $response->assertOk();
        $names = collect($response->json('data.data'))->pluck('name')->toArray();
        $this->assertContains('Phòng Suite Sang Trọng', $names);
        $this->assertNotContains('Phòng Standard Cơ Bản', $names);
    }

    public function test_TC_PRS_011_filter_by_keyword_matches_description(): void
    {
        $this->makeRoom(['name' => 'Phòng A', 'description' => 'View biển tuyệt đẹp']);
        $this->makeRoom(['name' => 'Phòng B', 'description' => 'Phòng nội thất đơn giản']);

        $response = $this->getJson('/api/v1/room-types?keyword=biển');

        $response->assertOk();
        $names = collect($response->json('data.data'))->pluck('name')->toArray();
        $this->assertContains('Phòng A', $names);
        $this->assertNotContains('Phòng B', $names);
    }

    // =========================================================
    // TC-PRS-020: Filter theo giá
    // =========================================================

    public function test_TC_PRS_020_filter_by_max_price(): void
    {
        $this->makeRoom(['price_per_night' => 500000]);
        $this->makeRoom(['price_per_night' => 1500000]);

        $response = $this->getJson('/api/v1/room-types?max_price=800000');

        $response->assertOk();
        $prices = collect($response->json('data.data'))->pluck('price_per_night')->map(fn ($p) => (float) $p)->toArray();
        foreach ($prices as $price) {
            $this->assertLessThanOrEqual(800000, $price);
        }
    }

    public function test_TC_PRS_021_filter_by_min_price(): void
    {
        $this->makeRoom(['price_per_night' => 300000]);
        $this->makeRoom(['price_per_night' => 1200000]);

        $response = $this->getJson('/api/v1/room-types?min_price=1000000');

        $response->assertOk();
        $prices = collect($response->json('data.data'))->pluck('price_per_night')->map(fn ($p) => (float) $p)->toArray();
        foreach ($prices as $price) {
            $this->assertGreaterThanOrEqual(1000000, $price);
        }
    }

    public function test_TC_PRS_022_max_price_less_than_min_price_returns_422(): void
    {
        $this->getJson('/api/v1/room-types?min_price=2000000&max_price=500000')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max_price']);
    }

    // =========================================================
    // TC-PRS-030: Filter theo sức chứa
    // =========================================================

    public function test_TC_PRS_030_filter_by_capacity(): void
    {
        $this->makeRoom(['capacity' => 2, 'name' => 'Phòng đôi']);
        $this->makeRoom(['capacity' => 4, 'name' => 'Phòng gia đình']);

        $response = $this->getJson('/api/v1/room-types?capacity=3');

        $response->assertOk();
        $names = collect($response->json('data.data'))->pluck('name')->toArray();
        $this->assertContains('Phòng gia đình', $names);
        $this->assertNotContains('Phòng đôi', $names);
    }

    public function test_TC_PRS_031_capacity_zero_returns_422(): void
    {
        $this->getJson('/api/v1/room-types?capacity=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['capacity']);
    }

    // =========================================================
    // TC-PRS-040: Validate check_in / check_out
    // =========================================================

    public function test_TC_PRS_040_invalid_check_in_format_returns_422(): void
    {
        $this->getJson('/api/v1/room-types?check_in=30/06/2026')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_in']);
    }

    public function test_TC_PRS_041_past_check_in_returns_422(): void
    {
        $this->getJson('/api/v1/room-types?check_in=2020-01-01&check_out=2020-01-05')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_in']);
    }

    public function test_TC_PRS_042_check_out_before_check_in_returns_422(): void
    {
        $checkIn  = now()->addDays(3)->toDateString();
        $checkOut = now()->addDay()->toDateString();

        $this->getJson("/api/v1/room-types?check_in={$checkIn}&check_out={$checkOut}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_out']);
    }

    public function test_TC_PRS_043_valid_dates_pass_and_return_rooms(): void
    {
        $this->makeRoom();
        $checkIn  = now()->addDays(2)->toDateString();
        $checkOut = now()->addDays(5)->toDateString();

        $this->getJson("/api/v1/room-types?check_in={$checkIn}&check_out={$checkOut}")
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    // =========================================================
    // TC-PRS-050: Kết quả sắp xếp tăng dần theo giá
    // =========================================================

    public function test_TC_PRS_050_results_sorted_by_price_ascending(): void
    {
        $this->makeRoom(['price_per_night' => 2000000]);
        $this->makeRoom(['price_per_night' => 600000]);
        $this->makeRoom(['price_per_night' => 1100000]);

        $response = $this->getJson('/api/v1/room-types');

        $prices = collect($response->json('data.data'))->pluck('price_per_night')->map(fn ($p) => (float) $p)->toArray();
        $sorted = $prices;
        sort($sorted);
        $this->assertEquals($sorted, $prices);
    }

    // =========================================================
    // TC-PRS-060: Phân trang
    // =========================================================

    public function test_TC_PRS_060_pagination_meta_present(): void
    {
        RoomType::factory()->count(5)->create(['status' => 'active']);

        $response = $this->getJson('/api/v1/room-types?per_page=3');

        $response->assertOk();
        $this->assertNotNull($response->json('data.total'));
        $this->assertNotNull($response->json('data.per_page'));
        $this->assertNotNull($response->json('data.current_page'));
    }

    public function test_TC_PRS_061_per_page_too_small_returns_422(): void
    {
        $this->getJson('/api/v1/room-types?per_page=2')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    // =========================================================
    // TC-PRS-070: Chi tiết phòng công khai GET /room-types/{id}
    // =========================================================

    public function test_TC_PRS_070_public_show_returns_active_room(): void
    {
        $room = $this->makeRoom();

        $this->getJson("/api/v1/room-types/{$room->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $room->id)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_TC_PRS_071_hidden_room_returns_404_to_public(): void
    {
        $room = $this->makeRoom(['status' => 'hidden']);

        $this->getJson("/api/v1/room-types/{$room->id}")
            ->assertNotFound();
    }

    public function test_TC_PRS_072_nonexistent_room_returns_404(): void
    {
        $this->getJson('/api/v1/room-types/999999')
            ->assertNotFound();
    }
}
