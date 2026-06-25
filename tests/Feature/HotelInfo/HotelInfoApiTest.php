<?php

namespace Tests\Feature\HotelInfo;

use App\Models\Amenity;
use App\Models\HotelInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test — module thông tin khách sạn singleton (hotel_info).
 *
 * Vì hệ thống Homi chỉ vận hành 1 khách sạn duy nhất, không còn
 * list/create/delete/restore — chỉ còn: xem (public + admin), cập nhật,
 * bật/tắt bảo trì, quản lý ảnh/tiện ích.
 *
 * Test case ID     | Chức năng                              | Kết quả mong đợi
 * TC-HI-001        | Public xem thông tin khách sạn          | 200, không cần đăng nhập
 * TC-HI-002        | Admin xem thông tin khách sạn            | 200
 * TC-HI-003        | Staff xem thông tin khách sạn            | 200
 * TC-HI-004        | Customer xem qua route admin             | 403
 * TC-HI-005        | Anonymous xem qua route admin             | 401
 * TC-HI-006        | Admin cập nhật thông tin                 | 200, dữ liệu lưu đúng
 * TC-HI-007        | Customer cập nhật thông tin               | 403
 * TC-HI-008        | Cập nhật thiếu name (rule sometimes)      | Không lỗi vì optional
 * TC-HI-009        | Cập nhật star_rating ngoài 1-5             | 422
 * TC-HI-010        | Cập nhật check_in_time sai định dạng       | 422
 * TC-HI-011        | Đồng bộ amenity_ids                       | Quan hệ many-to-many đúng
 * TC-HI-012        | Cập nhật thay toàn bộ ảnh (replace)        | Ảnh cũ bị xóa, ảnh mới đúng số lượng
 * TC-HI-013        | Toggle maintenance admin                   | 200, đổi active <-> maintenance
 * TC-HI-014        | Toggle maintenance customer                | 403
 * TC-HI-015        | Tài khoản admin bị khóa                    | 403 ACCOUNT_LOCKED
 * TC-HI-016        | hotel_info luôn chỉ có 1 bản ghi            | instance() không tạo thêm bản ghi
 */
class HotelInfoApiTest extends TestCase
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
    // TC-HI-001 đến 005: xem thông tin khách sạn
    // =========================================================

    public function test_TC_HI_001_guest_can_view_hotel_info(): void
    {
        HotelInfo::instance()->update(['name' => 'Homi Test Hotel']);

        $this->getJson('/api/v1/hotel-info')
            ->assertOk()
            ->assertJsonPath('data.name', 'Homi Test Hotel')
            ->assertJsonStructure([
                'data' => ['id', 'name', 'address', 'status', 'amenities', 'images'],
            ]);
    }

    public function test_TC_HI_002_admin_can_view_hotel_info(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/hotel-info')
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_TC_HI_003_staff_can_view_hotel_info(): void
    {
        $this->actingAs($this->staff)
            ->getJson('/api/v1/admin/hotel-info')
            ->assertOk();
    }

    public function test_TC_HI_004_customer_cannot_view_admin_hotel_info(): void
    {
        $this->actingAs($this->customer)
            ->getJson('/api/v1/admin/hotel-info')
            ->assertForbidden();
    }

    public function test_TC_HI_005_anonymous_cannot_view_admin_hotel_info(): void
    {
        $this->getJson('/api/v1/admin/hotel-info')
            ->assertUnauthorized();
    }

    // =========================================================
    // TC-HI-006 đến 010: cập nhật + validation
    // =========================================================

    public function test_TC_HI_006_admin_can_update_hotel_info(): void
    {
        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/hotel-info', [
                'name'    => 'Homi Updated',
                'address' => '999 Nguyễn Huệ, Quận 1',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Homi Updated');

        $this->assertDatabaseHas('hotel_info', ['name' => 'Homi Updated']);
        $this->assertDatabaseCount('hotel_info', 1);
    }

    public function test_TC_HI_007_customer_cannot_update_hotel_info(): void
    {
        $this->actingAs($this->customer)
            ->putJson('/api/v1/admin/hotel-info', ['name' => 'Hack'])
            ->assertForbidden();
    }

    public function test_TC_HI_008_update_without_name_is_allowed(): void
    {
        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/hotel-info', ['address' => 'Địa chỉ mới'])
            ->assertOk();
    }

    public function test_TC_HI_009_update_with_invalid_star_rating_returns_422(): void
    {
        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/hotel-info', ['star_rating' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['star_rating']);
    }

    public function test_TC_HI_010_update_with_invalid_check_in_time_returns_422(): void
    {
        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/hotel-info', ['check_in_time' => 'not-a-time'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_in_time']);
    }

    // =========================================================
    // TC-HI-011, 012: tiện ích + ảnh
    // =========================================================

    public function test_TC_HI_011_amenity_ids_are_synced(): void
    {
        $amenities = collect([
            Amenity::create(['name' => 'Wifi miễn phí', 'icon' => 'wifi']),
            Amenity::create(['name' => 'Bãi đỗ xe', 'icon' => 'parking']),
        ]);

        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/hotel-info', [
                'amenity_ids' => $amenities->pluck('id')->all(),
            ])
            ->assertOk();

        $this->assertDatabaseCount('hotel_info_amenity', 2);
    }

    public function test_TC_HI_012_update_replaces_all_images(): void
    {
        $hotel = HotelInfo::instance();
        $hotel->images()->createMany([
            ['path' => 'old/1.jpg', 'sort_order' => 0],
            ['path' => 'old/2.jpg', 'sort_order' => 1],
        ]);

        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/hotel-info', ['images' => ['new/1.jpg']])
            ->assertOk();

        $this->assertDatabaseCount('hotel_info_images', 1);
        $this->assertDatabaseHas('hotel_info_images', ['path' => 'new/1.jpg']);
        $this->assertDatabaseMissing('hotel_info_images', ['path' => 'old/1.jpg']);
    }

    // =========================================================
    // TC-HI-013, 014: bảo trì
    // =========================================================

    public function test_TC_HI_013_admin_can_toggle_maintenance(): void
    {
        HotelInfo::instance()->update(['status' => 'active']);

        $this->actingAs($this->admin)
            ->patchJson('/api/v1/admin/hotel-info/toggle-maintenance')
            ->assertOk()
            ->assertJsonPath('data.status', 'maintenance');

        $this->actingAs($this->admin)
            ->patchJson('/api/v1/admin/hotel-info/toggle-maintenance')
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_TC_HI_014_customer_cannot_toggle_maintenance(): void
    {
        $this->actingAs($this->customer)
            ->patchJson('/api/v1/admin/hotel-info/toggle-maintenance')
            ->assertForbidden();
    }

    public function test_room_type_creation_blocked_during_maintenance(): void
    {
        HotelInfo::instance()->update(['status' => 'maintenance']);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', [
                'name'            => 'Phòng Test',
                'price_per_night' => 500000,
                'capacity'        => 2,
                'total_rooms'     => 5,
            ])
            ->assertUnprocessable();
    }

    // =========================================================
    // TC-HI-015: tài khoản bị khóa
    // =========================================================

    public function test_TC_HI_015_locked_admin_cannot_access_hotel_info(): void
    {
        $locked = User::factory()->admin()->locked()->create();

        $this->actingAs($locked)
            ->getJson('/api/v1/admin/hotel-info')
            ->assertForbidden()
            ->assertJsonFragment(['error_code' => 'ACCOUNT_LOCKED']);
    }

    // =========================================================
    // TC-HI-016: tính singleton
    // =========================================================

    public function test_TC_HI_016_hotel_info_table_always_has_exactly_one_row(): void
    {
        HotelInfo::instance();
        HotelInfo::instance();
        HotelInfo::instance();

        $this->assertDatabaseCount('hotel_info', 1);
    }
}
