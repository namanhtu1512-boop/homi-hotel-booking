<?php

namespace Tests\Feature\Hotel;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test — kiểm tra phân quyền API /admin/hotels.
 *
 * Bộ test này xác nhận:
 *  - Admin và Staff được phép truy cập tất cả endpoint quản lý khách sạn.
 *  - Customer nhận 403 Forbidden khi gọi các endpoint admin.
 *  - Người dùng chưa đăng nhập nhận 401 Unauthenticated.
 *
 * Test case ID  | Chức năng                  | Role      | Kết quả mong đợi
 * TC-H-001      | Danh sách khách sạn        | admin     | 200
 * TC-H-002      | Danh sách khách sạn        | staff     | 200
 * TC-H-003      | Danh sách khách sạn        | customer  | 403
 * TC-H-004      | Danh sách khách sạn        | anonymous | 401
 * TC-H-005      | Tạo khách sạn              | admin     | 201
 * TC-H-006      | Tạo khách sạn              | staff     | 201
 * TC-H-007      | Tạo khách sạn              | customer  | 403
 * TC-H-008      | Tạo khách sạn              | anonymous | 401
 * TC-H-009      | Xem chi tiết khách sạn     | admin     | 200
 * TC-H-010      | Xem chi tiết khách sạn     | staff     | 200
 * TC-H-011      | Xem chi tiết khách sạn     | customer  | 403
 * TC-H-012      | Cập nhật khách sạn         | admin     | 200
 * TC-H-013      | Cập nhật khách sạn         | staff     | 200
 * TC-H-014      | Cập nhật khách sạn         | customer  | 403
 * TC-H-015      | Xóa mềm khách sạn          | admin     | 200
 * TC-H-016      | Xóa mềm khách sạn          | staff     | 200
 * TC-H-017      | Xóa mềm khách sạn          | customer  | 403
 * TC-H-018      | Khôi phục khách sạn        | admin     | 200
 * TC-H-019      | Khôi phục khách sạn        | staff     | 200
 * TC-H-020      | Khôi phục khách sạn        | customer  | 403
 * TC-H-021      | Toggle status              | admin     | 200
 * TC-H-022      | Toggle status              | staff     | 200
 * TC-H-023      | Toggle status              | customer  | 403
 */
class AdminHotelAccessTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function makeHotel(array $attributes = []): Hotel
    {
        return Hotel::factory()->create(array_merge([
            'name'    => 'Khách sạn Test',
            'city'    => 'Hà Nội',
            'address' => '1 Hàng Bài',
            'status'  => 'active',
        ], $attributes));
    }

    private function hotelPayload(): array
    {
        return [
            'name'    => 'Khách sạn Mới',
            'city'    => 'Hà Nội',
            'address' => '123 Phố Huế',
        ];
    }

    // ----------------------------------------------------------------
    // TC-H-001 đến TC-H-004: GET /admin/hotels (danh sách)
    // ----------------------------------------------------------------

    public function test_admin_can_list_hotels(): void // TC-H-001
    {
        $this->actingAs($this->makeUser('admin'))
            ->getJson('/api/v1/admin/hotels')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['hotels', 'meta']]);
    }

    public function test_staff_can_list_hotels(): void // TC-H-002
    {
        $this->actingAs($this->makeUser('staff'))
            ->getJson('/api/v1/admin/hotels')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['hotels', 'meta']]);
    }

    public function test_customer_cannot_list_hotels(): void // TC-H-003
    {
        $this->actingAs($this->makeUser('customer'))
            ->getJson('/api/v1/admin/hotels')
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_anonymous_cannot_list_hotels(): void // TC-H-004
    {
        $this->getJson('/api/v1/admin/hotels')
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    // ----------------------------------------------------------------
    // TC-H-005 đến TC-H-008: POST /admin/hotels (tạo)
    // ----------------------------------------------------------------

    public function test_admin_can_create_hotel(): void // TC-H-005
    {
        $this->actingAs($this->makeUser('admin'))
            ->postJson('/api/v1/admin/hotels', $this->hotelPayload())
            ->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_staff_can_create_hotel(): void // TC-H-006
    {
        $this->actingAs($this->makeUser('staff'))
            ->postJson('/api/v1/admin/hotels', $this->hotelPayload())
            ->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_create_hotel(): void // TC-H-007
    {
        $this->actingAs($this->makeUser('customer'))
            ->postJson('/api/v1/admin/hotels', $this->hotelPayload())
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_anonymous_cannot_create_hotel(): void // TC-H-008
    {
        $this->postJson('/api/v1/admin/hotels', $this->hotelPayload())
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    // ----------------------------------------------------------------
    // TC-H-009 đến TC-H-011: GET /admin/hotels/{id} (chi tiết)
    // ----------------------------------------------------------------

    public function test_admin_can_view_hotel_detail(): void // TC-H-009
    {
        $hotel = $this->makeHotel();

        $this->actingAs($this->makeUser('admin'))
            ->getJson("/api/v1/admin/hotels/{$hotel->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_staff_can_view_hotel_detail(): void // TC-H-010
    {
        $hotel = $this->makeHotel();

        $this->actingAs($this->makeUser('staff'))
            ->getJson("/api/v1/admin/hotels/{$hotel->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_view_hotel_detail(): void // TC-H-011
    {
        $hotel = $this->makeHotel();

        $this->actingAs($this->makeUser('customer'))
            ->getJson("/api/v1/admin/hotels/{$hotel->id}")
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    // ----------------------------------------------------------------
    // TC-H-012 đến TC-H-014: PUT /admin/hotels/{id} (cập nhật)
    // ----------------------------------------------------------------

    public function test_admin_can_update_hotel(): void // TC-H-012
    {
        $hotel = $this->makeHotel();

        $this->actingAs($this->makeUser('admin'))
            ->putJson("/api/v1/admin/hotels/{$hotel->id}", ['name' => 'Tên Mới'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_staff_can_update_hotel(): void // TC-H-013
    {
        $hotel = $this->makeHotel();

        $this->actingAs($this->makeUser('staff'))
            ->putJson("/api/v1/admin/hotels/{$hotel->id}", ['name' => 'Tên Mới 2'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_update_hotel(): void // TC-H-014
    {
        $hotel = $this->makeHotel();

        $this->actingAs($this->makeUser('customer'))
            ->putJson("/api/v1/admin/hotels/{$hotel->id}", ['name' => 'Hack tên'])
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    // ----------------------------------------------------------------
    // TC-H-015 đến TC-H-017: DELETE /admin/hotels/{id} (xóa mềm)
    // ----------------------------------------------------------------

    public function test_admin_can_soft_delete_hotel(): void // TC-H-015
    {
        $hotel = $this->makeHotel();

        $this->actingAs($this->makeUser('admin'))
            ->deleteJson("/api/v1/admin/hotels/{$hotel->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('hotels', ['id' => $hotel->id]);
    }

    public function test_staff_can_soft_delete_hotel(): void // TC-H-016
    {
        $hotel = $this->makeHotel();

        $this->actingAs($this->makeUser('staff'))
            ->deleteJson("/api/v1/admin/hotels/{$hotel->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('hotels', ['id' => $hotel->id]);
    }

    public function test_customer_cannot_soft_delete_hotel(): void // TC-H-017
    {
        $hotel = $this->makeHotel();

        $this->actingAs($this->makeUser('customer'))
            ->deleteJson("/api/v1/admin/hotels/{$hotel->id}")
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    // ----------------------------------------------------------------
    // TC-H-018 đến TC-H-020: POST /admin/hotels/{id}/restore (khôi phục)
    // ----------------------------------------------------------------

    public function test_admin_can_restore_hotel(): void // TC-H-018
    {
        $hotel = $this->makeHotel();
        $hotel->delete();

        $this->actingAs($this->makeUser('admin'))
            ->postJson("/api/v1/admin/hotels/{$hotel->id}/restore")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNotSoftDeleted('hotels', ['id' => $hotel->id]);
    }

    public function test_staff_can_restore_hotel(): void // TC-H-019
    {
        $hotel = $this->makeHotel();
        $hotel->delete();

        $this->actingAs($this->makeUser('staff'))
            ->postJson("/api/v1/admin/hotels/{$hotel->id}/restore")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_restore_hotel(): void // TC-H-020
    {
        $hotel = $this->makeHotel();
        $hotel->delete();

        $this->actingAs($this->makeUser('customer'))
            ->postJson("/api/v1/admin/hotels/{$hotel->id}/restore")
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    // ----------------------------------------------------------------
    // TC-H-021 đến TC-H-023: PATCH /admin/hotels/{id}/toggle-status
    // ----------------------------------------------------------------

    public function test_admin_can_toggle_hotel_status(): void // TC-H-021
    {
        $hotel = $this->makeHotel(['status' => 'active']);

        $this->actingAs($this->makeUser('admin'))
            ->patchJson("/api/v1/admin/hotels/{$hotel->id}/toggle-status")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('hotels', ['id' => $hotel->id, 'status' => 'hidden']);
    }

    public function test_staff_can_toggle_hotel_status(): void // TC-H-022
    {
        $hotel = $this->makeHotel(['status' => 'active']);

        $this->actingAs($this->makeUser('staff'))
            ->patchJson("/api/v1/admin/hotels/{$hotel->id}/toggle-status")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_toggle_hotel_status(): void // TC-H-023
    {
        $hotel = $this->makeHotel();

        $this->actingAs($this->makeUser('customer'))
            ->patchJson("/api/v1/admin/hotels/{$hotel->id}/toggle-status")
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    // ----------------------------------------------------------------
    // Kiểm tra meta sort trong response danh sách
    // ----------------------------------------------------------------

    public function test_list_response_includes_sort_meta(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->getJson('/api/v1/admin/hotels?sort_by=name&sort_order=asc')
            ->assertStatus(200)
            ->assertJsonPath('data.meta.sort_by', 'name')
            ->assertJsonPath('data.meta.sort_order', 'asc');
    }

    public function test_list_defaults_to_created_at_desc(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->getJson('/api/v1/admin/hotels')
            ->assertStatus(200)
            ->assertJsonPath('data.meta.sort_by', 'created_at')
            ->assertJsonPath('data.meta.sort_order', 'desc');
    }

    public function test_invalid_sort_by_returns_422(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->getJson('/api/v1/admin/hotels?sort_by=invalid_column')
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_invalid_sort_order_returns_422(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->getJson('/api/v1/admin/hotels?sort_order=sideways')
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    // ----------------------------------------------------------------
    // Tài khoản bị khóa
    // ----------------------------------------------------------------

    public function test_locked_admin_cannot_access_hotels(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'locked']);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/hotels')
            ->assertStatus(403)
            ->assertJsonFragment(['error_code' => 'ACCOUNT_LOCKED']);
    }
}
