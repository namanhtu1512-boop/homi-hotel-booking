<?php

namespace Tests\Feature\Hotel;

use App\Models\Amenity;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test — kiểm tra logic CRUD và dữ liệu của module Hotels (admin).
 *
 * Khác với AdminHotelAccessTest (chỉ test phân quyền), bộ test này tập trung
 * vào tính đúng đắn của dữ liệu: validation thiếu trường, trùng tên, ảnh lỗi,
 * và hành vi xóa mềm / khôi phục.
 *
 * Test case ID    | Chức năng                              | Kết quả mong đợi
 * TC-HCRUD-001    | Tạo khách sạn thiếu name                | 422 — errors.name
 * TC-HCRUD-002    | Tạo khách sạn thiếu city                | 422 — errors.city
 * TC-HCRUD-003    | Tạo khách sạn thiếu address              | 422 — errors.address
 * TC-HCRUD-004    | Tạo khách sạn trùng tên                 | 201 — 2 khách sạn, slug khác nhau
 * TC-HCRUD-005    | Tạo khách sạn với star_rating ngoài 1-5  | 422 — errors.star_rating
 * TC-HCRUD-006    | Tạo khách sạn với images không phải string | 422 — errors.images.0
 * TC-HCRUD-007    | Tạo khách sạn với image path quá dài     | 422 — errors.images.0
 * TC-HCRUD-008    | Tạo khách sạn với amenity_id không tồn tại | 422 — errors.amenity_ids.0
 * TC-HCRUD-009    | Tạo khách sạn hợp lệ kèm ảnh + tiện ích   | 201 — quan hệ được lưu đúng
 * TC-HCRUD-010    | Cập nhật khách sạn đổi tên trùng tên khác | 200 — slug mới không đụng slug cũ
 * TC-HCRUD-011    | Cập nhật thay toàn bộ ảnh (replace)       | 200 — ảnh cũ bị xóa, ảnh mới đúng số lượng
 * TC-HCRUD-012    | Xóa mềm khách sạn                        | Không còn trong danh sách public
 * TC-HCRUD-013    | Xóa mềm khách sạn                        | Xem chi tiết public trả 404
 * TC-HCRUD-014    | Khôi phục khách sạn đã xóa mềm            | Xuất hiện lại trong danh sách public
 * TC-HCRUD-015    | Xóa khách sạn không tồn tại                | 404
 * TC-HCRUD-016    | Toggle status active -> hidden -> active   | Khách sạn hidden không lộ ở public
 */
class AdminHotelCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'name'    => 'Homi Test Hotel',
            'city'    => 'Hà Nội',
            'address' => '1 Hàng Bài, Hoàn Kiếm, Hà Nội',
        ], $overrides);
    }

    // ----------------------------------------------------------------
    // TC-HCRUD-001 đến 003: thiếu trường bắt buộc
    // ----------------------------------------------------------------

    public function test_create_fails_when_name_missing(): void // TC-HCRUD-001
    {
        $payload = $this->basePayload();
        unset($payload['name']);

        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/hotels', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_fails_when_city_missing(): void // TC-HCRUD-002
    {
        $payload = $this->basePayload();
        unset($payload['city']);

        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/hotels', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['city']);
    }

    public function test_create_fails_when_address_missing(): void // TC-HCRUD-003
    {
        $payload = $this->basePayload();
        unset($payload['address']);

        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/hotels', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address']);
    }

    // ----------------------------------------------------------------
    // TC-HCRUD-004: trùng tên khách sạn vẫn tạo được, slug tự sinh khác nhau
    // ----------------------------------------------------------------

    public function test_create_with_duplicate_name_succeeds_with_unique_slug(): void // TC-HCRUD-004
    {
        $admin = $this->admin();

        $first = $this->actingAs($admin)
            ->postJson('/api/v1/admin/hotels', $this->basePayload(['name' => 'Homi Sài Gòn']))
            ->assertStatus(201)
            ->json('data');

        $second = $this->actingAs($admin)
            ->postJson('/api/v1/admin/hotels', $this->basePayload([
                'name'    => 'Homi Sài Gòn',
                'city'    => 'TP Hồ Chí Minh',
                'address' => '2 Lê Lợi, Quận 1',
            ]))
            ->assertStatus(201)
            ->json('data');

        $this->assertSame('Homi Sài Gòn', $first['name']);
        $this->assertSame('Homi Sài Gòn', $second['name']);
        $this->assertNotSame($first['slug'], $second['slug']);

        $this->assertDatabaseCount('hotels', 2);
    }

    // ----------------------------------------------------------------
    // TC-HCRUD-005: star_rating ngoài khoảng 1-5
    // ----------------------------------------------------------------

    public function test_create_fails_when_star_rating_out_of_range(): void // TC-HCRUD-005
    {
        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/hotels', $this->basePayload(['star_rating' => 6]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['star_rating']);
    }

    // ----------------------------------------------------------------
    // TC-HCRUD-006, 007: ảnh lỗi
    // ----------------------------------------------------------------

    public function test_create_fails_when_image_entry_is_not_string(): void // TC-HCRUD-006
    {
        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/hotels', $this->basePayload([
                'images' => [12345],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['images.0']);
    }

    public function test_create_fails_when_image_path_too_long(): void // TC-HCRUD-007
    {
        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/hotels', $this->basePayload([
                'images' => [str_repeat('a', 501)],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['images.0']);
    }

    // ----------------------------------------------------------------
    // TC-HCRUD-008: amenity_id không tồn tại
    // ----------------------------------------------------------------

    public function test_create_fails_when_amenity_id_does_not_exist(): void // TC-HCRUD-008
    {
        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/hotels', $this->basePayload([
                'amenity_ids' => [99999],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amenity_ids.0']);
    }

    // ----------------------------------------------------------------
    // TC-HCRUD-009: tạo hợp lệ kèm ảnh + tiện ích
    // ----------------------------------------------------------------

    public function test_create_with_images_and_amenities_persists_relations(): void // TC-HCRUD-009
    {
        $amenities = collect([
            Amenity::create(['name' => 'Wifi miễn phí', 'icon' => 'wifi']),
            Amenity::create(['name' => 'Bãi đỗ xe', 'icon' => 'parking']),
        ]);

        $response = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/hotels', $this->basePayload([
                'amenity_ids' => $amenities->pluck('id')->all(),
                'images'      => ['hotels/1.jpg', 'hotels/2.jpg'],
            ]))
            ->assertStatus(201);

        $hotelId = $response->json('data.id');

        $this->assertDatabaseCount('hotel_images', 2);
        $this->assertDatabaseHas('hotel_amenity', [
            'hotel_id'   => $hotelId,
            'amenity_id' => $amenities->first()->id,
        ]);
    }

    // ----------------------------------------------------------------
    // TC-HCRUD-010: update đổi tên trùng tên khách sạn khác
    // ----------------------------------------------------------------

    public function test_update_to_duplicate_name_gets_unique_slug(): void // TC-HCRUD-010
    {
        $admin = $this->admin();

        $existing = Hotel::factory()->create(['name' => 'Homi Đà Lạt', 'slug' => 'homi-da-lat']);
        $other    = Hotel::factory()->create(['name' => 'Homi Khác', 'slug' => 'homi-khac']);

        $this->actingAs($admin)
            ->putJson("/api/v1/admin/hotels/{$other->id}", ['name' => 'Homi Đà Lạt'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $other->refresh();

        $this->assertSame('Homi Đà Lạt', $other->name);
        $this->assertNotSame($existing->slug, $other->slug);
    }

    // ----------------------------------------------------------------
    // TC-HCRUD-011: update thay toàn bộ ảnh
    // ----------------------------------------------------------------

    public function test_update_replaces_all_images(): void // TC-HCRUD-011
    {
        $hotel = Hotel::factory()->create();
        $hotel->images()->createMany([
            ['path' => 'old/1.jpg', 'sort_order' => 0],
            ['path' => 'old/2.jpg', 'sort_order' => 1],
        ]);

        $this->actingAs($this->admin())
            ->putJson("/api/v1/admin/hotels/{$hotel->id}", [
                'images' => ['new/1.jpg'],
            ])
            ->assertStatus(200);

        $this->assertDatabaseCount('hotel_images', 1);
        $this->assertDatabaseHas('hotel_images', ['hotel_id' => $hotel->id, 'path' => 'new/1.jpg']);
        $this->assertDatabaseMissing('hotel_images', ['path' => 'old/1.jpg']);
    }

    // ----------------------------------------------------------------
    // TC-HCRUD-012 đến 014: xóa mềm / khôi phục
    // ----------------------------------------------------------------

    public function test_soft_deleted_hotel_disappears_from_public_list(): void // TC-HCRUD-012
    {
        $hotel = Hotel::factory()->create(['status' => 'active']);

        $this->actingAs($this->admin())
            ->deleteJson("/api/v1/admin/hotels/{$hotel->id}")
            ->assertStatus(200);

        $this->getJson('/api/v1/hotels')
            ->assertStatus(200)
            ->assertJsonMissing(['id' => $hotel->id]);
    }

    public function test_soft_deleted_hotel_returns_404_on_public_detail(): void // TC-HCRUD-013
    {
        $hotel = Hotel::factory()->create(['status' => 'active']);
        $hotel->delete();

        $this->getJson("/api/v1/hotels/{$hotel->id}")
            ->assertStatus(404);
    }

    public function test_restored_hotel_reappears_in_public_list(): void // TC-HCRUD-014
    {
        $hotel = Hotel::factory()->create(['status' => 'active']);
        $hotel->delete();

        $this->actingAs($this->admin())
            ->postJson("/api/v1/admin/hotels/{$hotel->id}/restore")
            ->assertStatus(200);

        $this->getJson("/api/v1/hotels/{$hotel->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $hotel->id);
    }

    public function test_delete_nonexistent_hotel_returns_404(): void // TC-HCRUD-015
    {
        $this->actingAs($this->admin())
            ->deleteJson('/api/v1/admin/hotels/999999')
            ->assertStatus(404);
    }

    // ----------------------------------------------------------------
    // TC-HCRUD-016: toggle status ẩn khách sạn khỏi public
    // ----------------------------------------------------------------

    public function test_hidden_hotel_not_visible_in_public_list(): void // TC-HCRUD-016
    {
        $hotel = Hotel::factory()->create(['status' => 'active']);

        $this->actingAs($this->admin())
            ->patchJson("/api/v1/admin/hotels/{$hotel->id}/toggle-status")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'hidden');

        $this->getJson('/api/v1/hotels')
            ->assertStatus(200)
            ->assertJsonMissing(['id' => $hotel->id]);
    }
}
