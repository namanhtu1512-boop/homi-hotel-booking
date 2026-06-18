<?php

namespace Tests\Feature\Hotel;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE4 - Tuần 5: Automated Feature Test module Hotels
 * Phạm vi: CRUD hotels, phân quyền admin/staff/customer, ảnh, soft delete
 *
 * Lưu ý: file này được BE1 hiệu chỉnh lại (tuần 6) cho khớp với contract thật
 * của API (do BE2 triển khai), khác với bản nháp ban đầu ở các điểm:
 *  - Trạng thái khách sạn dùng `status` enum (active|hidden), không phải `is_active` boolean.
 *  - Ảnh khách sạn là mảng đường dẫn string (`images: string[]`), API không nhận
 *    multipart file upload trực tiếp — không có endpoint validate file ảnh thật.
 *  - Response danh sách lồng trong `data.hotels` + `data.meta` (chuẩn ApiResponse::paginated()),
 *    không phải `data` + `meta` ở cấp ngoài cùng.
 *  - Route ẩn/hiện khách sạn là `toggle-status`, không phải `toggle-active`.
 *  - Xóa mềm trả về 200 (có message), không phải 204 No Content.
 *
 * Chạy: php artisan test --filter=HotelApiTest
 *       php artisan test tests/Feature/Hotel/HotelApiTest.php
 */
class HotelApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin    = User::factory()->admin()->create();
        $this->staff    = User::factory()->staff()->create();
        $this->customer = User::factory()->customer()->create();
    }

    // =========================================================
    // TC-HTL-001: Lấy danh sách khách sạn (public)
    // =========================================================

    /** @test */
    public function test_TC_HTL_001_guest_can_list_active_hotels(): void
    {
        Hotel::factory()->count(5)->create(['status' => 'active']);
        Hotel::factory()->count(2)->create(['status' => 'hidden']);

        $response = $this->getJson('/api/v1/hotels');

        $response->assertOk()
                 ->assertJsonStructure([
                     'data' => [
                         'hotels' => [['id', 'name', 'address', 'city', 'star_rating']],
                         'meta'   => ['current_page', 'total', 'per_page'],
                     ],
                 ]);

        // Chỉ trả khách sạn active
        $this->assertCount(5, $response->json('data.hotels'));
    }

    /** @test */
    public function test_TC_HTL_002_list_hotels_paginated_default_10(): void
    {
        Hotel::factory()->count(20)->create(['status' => 'active']);

        $response = $this->getJson('/api/v1/hotels');

        $response->assertOk();
        // PublicHotelController mặc định per_page=10
        $this->assertCount(10, $response->json('data.hotels'));
        $this->assertEquals(20, $response->json('data.meta.total'));
    }

    // =========================================================
    // TC-HTL-010: Admin xem danh sách tất cả khách sạn (kể cả hidden)
    // =========================================================

    /** @test */
    public function test_TC_HTL_010_admin_can_list_all_hotels_including_hidden(): void
    {
        Hotel::factory()->count(3)->create(['status' => 'active']);
        Hotel::factory()->count(2)->create(['status' => 'hidden']);

        $response = $this->actingAs($this->admin)
                         ->getJson('/api/v1/admin/hotels');

        $response->assertOk();
        $this->assertEquals(5, $response->json('data.meta.total'));
    }

    /** @test */
    public function test_TC_HTL_011_customer_cannot_access_admin_hotel_list(): void
    {
        $this->actingAs($this->customer)
             ->getJson('/api/v1/admin/hotels')
             ->assertForbidden();
    }

    // =========================================================
    // TC-HTL-020: Admin tạo khách sạn
    // =========================================================

    /** @test */
    public function test_TC_HTL_020_admin_can_create_hotel_with_valid_data(): void
    {
        $payload = $this->validHotelPayload();

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/v1/admin/hotels', $payload);

        $response->assertCreated()
                 ->assertJsonPath('data.name', $payload['name'])
                 ->assertJsonPath('data.city', $payload['city'])
                 ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('hotels', ['name' => $payload['name']]);
    }

    /** @test */
    public function test_TC_HTL_021_staff_can_create_hotel(): void
    {
        $response = $this->actingAs($this->staff)
                         ->postJson('/api/v1/admin/hotels', $this->validHotelPayload());

        $response->assertCreated();
    }

    /** @test */
    public function test_TC_HTL_022_customer_cannot_create_hotel(): void
    {
        $this->actingAs($this->customer)
             ->postJson('/api/v1/admin/hotels', $this->validHotelPayload())
             ->assertForbidden();
    }

    /** @test */
    public function test_TC_HTL_023_unauthenticated_cannot_create_hotel(): void
    {
        $this->postJson('/api/v1/admin/hotels', $this->validHotelPayload())
             ->assertUnauthorized();
    }

    // =========================================================
    // TC-HTL-030: Validation khi tạo khách sạn
    // =========================================================

    /** @test */
    public function test_TC_HTL_030_create_hotel_missing_name_returns_422(): void
    {
        $payload = $this->validHotelPayload();
        unset($payload['name']);

        $this->actingAs($this->admin)
             ->postJson('/api/v1/admin/hotels', $payload)
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function test_TC_HTL_031_create_hotel_missing_address_returns_422(): void
    {
        $payload = $this->validHotelPayload();
        unset($payload['address']);

        $this->actingAs($this->admin)
             ->postJson('/api/v1/admin/hotels', $payload)
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['address']);
    }

    /** @test */
    public function test_TC_HTL_032_create_hotel_invalid_star_rating_returns_422(): void
    {
        $payload = $this->validHotelPayload(['star_rating' => 6]);

        $this->actingAs($this->admin)
             ->postJson('/api/v1/admin/hotels', $payload)
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['star_rating']);
    }

    /** @test */
    public function test_TC_HTL_033_create_hotel_name_too_long_returns_422(): void
    {
        $payload = $this->validHotelPayload(['name' => str_repeat('A', 256)]);

        $this->actingAs($this->admin)
             ->postJson('/api/v1/admin/hotels', $payload)
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function test_TC_HTL_034_create_hotel_missing_city_returns_422(): void
    {
        $payload = $this->validHotelPayload();
        unset($payload['city']);

        $this->actingAs($this->admin)
             ->postJson('/api/v1/admin/hotels', $payload)
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['city']);
    }

    // =========================================================
    // TC-HTL-040: Ảnh khách sạn (mảng đường dẫn string, không phải file upload)
    // =========================================================

    /** @test */
    public function test_TC_HTL_040_create_hotel_with_image_paths(): void
    {
        $payload            = $this->validHotelPayload();
        $payload['images']  = ['https://cdn.homi.vn/hotel-1.jpg'];

        $response = $this->actingAs($this->admin)
                         ->postJson('/api/v1/admin/hotels', $payload);

        $response->assertCreated();
        $hotelId = $response->json('data.id');

        $this->assertDatabaseHas('hotel_images', [
            'hotel_id' => $hotelId,
            'path'     => 'https://cdn.homi.vn/hotel-1.jpg',
        ]);
    }

    /** @test */
    public function test_TC_HTL_041_create_hotel_with_non_string_image_returns_422(): void
    {
        $payload           = $this->validHotelPayload();
        $payload['images'] = [12345];

        $this->actingAs($this->admin)
             ->postJson('/api/v1/admin/hotels', $payload)
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['images.0']);
    }

    /** @test */
    public function test_TC_HTL_042_create_hotel_with_oversized_image_path_returns_422(): void
    {
        $payload           = $this->validHotelPayload();
        $payload['images'] = [str_repeat('a', 501)];

        $this->actingAs($this->admin)
             ->postJson('/api/v1/admin/hotels', $payload)
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['images.0']);
    }

    // =========================================================
    // TC-HTL-050: Xem chi tiết khách sạn
    // =========================================================

    /** @test */
    public function test_TC_HTL_050_anyone_can_view_active_hotel_detail(): void
    {
        $hotel = Hotel::factory()->create(['status' => 'active']);

        $this->getJson("/api/v1/hotels/{$hotel->id}")
             ->assertOk()
             ->assertJsonPath('data.id', $hotel->id)
             ->assertJsonStructure([
                 'data' => ['id', 'name', 'address', 'city', 'description', 'star_rating', 'amenities', 'images'],
             ]);
    }

    /** @test */
    public function test_TC_HTL_051_hidden_hotel_not_visible_to_public(): void
    {
        $hotel = Hotel::factory()->create(['status' => 'hidden']);

        $this->getJson("/api/v1/hotels/{$hotel->id}")
             ->assertNotFound();
    }

    /** @test */
    public function test_TC_HTL_052_admin_can_view_hidden_hotel_detail(): void
    {
        $hotel = Hotel::factory()->create(['status' => 'hidden']);

        $this->actingAs($this->admin)
             ->getJson("/api/v1/admin/hotels/{$hotel->id}")
             ->assertOk();
    }

    /** @test */
    public function test_TC_HTL_053_view_nonexistent_hotel_returns_404(): void
    {
        $this->getJson('/api/v1/hotels/999999')
             ->assertNotFound();
    }

    // =========================================================
    // TC-HTL-060: Cập nhật khách sạn
    // =========================================================

    /** @test */
    public function test_TC_HTL_060_admin_can_update_hotel(): void
    {
        $hotel = Hotel::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin)
                         ->putJson("/api/v1/admin/hotels/{$hotel->id}", [
                             'name' => 'New Name',
                         ]);

        $response->assertOk()
                 ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('hotels', ['id' => $hotel->id, 'name' => 'New Name']);
    }

    /** @test */
    public function test_TC_HTL_061_customer_cannot_update_hotel(): void
    {
        $hotel = Hotel::factory()->create();

        $this->actingAs($this->customer)
             ->putJson("/api/v1/admin/hotels/{$hotel->id}", ['name' => 'Hack'])
             ->assertForbidden();
    }

    // =========================================================
    // TC-HTL-070: Toggle active/hidden khách sạn
    // =========================================================

    /** @test */
    public function test_TC_HTL_070_admin_can_toggle_hotel_status_to_hidden(): void
    {
        $hotel = Hotel::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)
                         ->patchJson("/api/v1/admin/hotels/{$hotel->id}/toggle-status");

        $response->assertOk()
                 ->assertJsonPath('data.status', 'hidden');

        $this->assertDatabaseHas('hotels', ['id' => $hotel->id, 'status' => 'hidden']);
    }

    /** @test */
    public function test_TC_HTL_071_toggle_hidden_hotel_becomes_active(): void
    {
        $hotel = Hotel::factory()->create(['status' => 'hidden']);

        $this->actingAs($this->admin)
             ->patchJson("/api/v1/admin/hotels/{$hotel->id}/toggle-status")
             ->assertOk()
             ->assertJsonPath('data.status', 'active');
    }

    // =========================================================
    // TC-HTL-080: Soft delete khách sạn
    // =========================================================

    /** @test */
    public function test_TC_HTL_080_admin_can_soft_delete_hotel(): void
    {
        $hotel = Hotel::factory()->create();

        $this->actingAs($this->admin)
             ->deleteJson("/api/v1/admin/hotels/{$hotel->id}")
             ->assertOk()
             ->assertJson(['success' => true]);

        $this->assertSoftDeleted('hotels', ['id' => $hotel->id]);
    }

    /** @test */
    public function test_TC_HTL_081_soft_deleted_hotel_not_in_public_list(): void
    {
        $hotel = Hotel::factory()->create(['status' => 'active']);
        $hotel->delete(); // soft delete

        $response = $this->getJson('/api/v1/hotels');

        $ids = collect($response->json('data.hotels'))->pluck('id')->toArray();
        $this->assertNotContains($hotel->id, $ids);
    }

    /** @test */
    public function test_TC_HTL_082_customer_cannot_delete_hotel(): void
    {
        $hotel = Hotel::factory()->create();

        $this->actingAs($this->customer)
             ->deleteJson("/api/v1/admin/hotels/{$hotel->id}")
             ->assertForbidden();
    }

    // =========================================================
    // TC-HTL-090: Kiểm thử dữ liệu lớn (20-50 khách sạn)
    // =========================================================

    /** @test */
    public function test_TC_HTL_090_list_with_50_hotels_returns_paginated_correctly(): void
    {
        Hotel::factory()->count(50)->create(['status' => 'active']);

        $response = $this->getJson('/api/v1/hotels?per_page=15&page=1');
        $response->assertOk();
        $this->assertCount(15, $response->json('data.hotels'));
        $this->assertEquals(50, $response->json('data.meta.total'));

        // Trang 4 chỉ có 5 records
        $page4 = $this->getJson('/api/v1/hotels?per_page=15&page=4');
        $this->assertCount(5, $page4->json('data.hotels'));
    }

    /** @test */
    public function test_TC_HTL_091_admin_list_50_hotels_performance_under_500ms(): void
    {
        Hotel::factory()->count(50)->create(['status' => 'active']);

        $start = microtime(true);
        $this->actingAs($this->admin)->getJson('/api/v1/admin/hotels');
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $elapsed, 'API list 50 hotels phải hoàn thành dưới 500ms');
    }

    // =========================================================
    // TC-HTL-100: Kiểm tra response không lộ dữ liệu nhạy cảm
    // =========================================================

    /** @test */
    public function test_TC_HTL_100_public_hotel_response_does_not_expose_admin_fields(): void
    {
        $hotel = Hotel::factory()->create(['status' => 'active']);

        $response = $this->getJson("/api/v1/hotels/{$hotel->id}");

        // Không được lộ các trường quản trị
        $response->assertJsonMissingPath('data.deleted_at')
                 ->assertJsonMissingPath('data.created_by')
                 ->assertJsonMissingPath('data.updated_by');
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function validHotelPayload(array $override = []): array
    {
        return array_merge([
            'name'        => 'Homi Luxury Hotel',
            'address'     => '123 Lê Lợi, Quận 1',
            'city'        => 'Hồ Chí Minh',
            'description' => 'Khách sạn 5 sao tại trung tâm thành phố.',
            'star_rating' => 5,
        ], $override);
    }
}
