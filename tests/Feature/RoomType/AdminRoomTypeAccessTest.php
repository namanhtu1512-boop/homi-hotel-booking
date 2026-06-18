<?php

namespace Tests\Feature\RoomType;

use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test — kiểm tra phân quyền API /admin/hotels/{hotelId}/room-types
 * và /admin/room-types/{id}.
 *
 * Test case ID  | Chức năng                      | Role      | Kết quả mong đợi
 * TC-RT-001     | Danh sách loại phòng           | admin     | 200
 * TC-RT-002     | Danh sách loại phòng           | staff     | 200
 * TC-RT-003     | Danh sách loại phòng           | customer  | 403
 * TC-RT-004     | Danh sách loại phòng           | anonymous | 401
 * TC-RT-005     | Tạo loại phòng                 | admin     | 201
 * TC-RT-006     | Tạo loại phòng                 | staff     | 201
 * TC-RT-007     | Tạo loại phòng                 | customer  | 403
 * TC-RT-008     | Tạo loại phòng khi hotel ẩn    | admin     | 422
 * TC-RT-009     | Xem chi tiết loại phòng        | admin     | 200
 * TC-RT-010     | Xem chi tiết loại phòng        | staff     | 200
 * TC-RT-011     | Xem chi tiết loại phòng        | customer  | 403
 * TC-RT-012     | Cập nhật loại phòng            | admin     | 200
 * TC-RT-013     | Cập nhật loại phòng            | staff     | 200
 * TC-RT-014     | Cập nhật loại phòng            | customer  | 403
 * TC-RT-015     | Cập nhật loại phòng khi hotel ẩn | staff   | 200 (vẫn cho phép sửa)
 * TC-RT-016     | Xóa loại phòng                 | admin     | 200
 * TC-RT-017     | Xóa loại phòng                 | staff     | 200
 * TC-RT-018     | Xóa loại phòng                 | customer  | 403
 * TC-RT-019     | Khôi phục loại phòng           | admin     | 200
 * TC-RT-020     | Khôi phục loại phòng           | staff     | 200
 * TC-RT-021     | Khôi phục loại phòng           | customer  | 403
 * TC-RT-022     | Đổi giá phòng                  | admin     | 200
 * TC-RT-023     | Đổi giá phòng                  | staff     | 200
 * TC-RT-024     | Đổi giá phòng                  | customer  | 403
 * TC-RT-025     | Đổi số lượng phòng             | admin     | 200
 * TC-RT-026     | Đổi số lượng phòng             | staff     | 200
 * TC-RT-027     | Đổi số lượng phòng             | customer  | 403
 */
class AdminRoomTypeAccessTest extends TestCase
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
        return Hotel::factory()->create($attributes);
    }

    private function makeRoomType(Hotel $hotel, array $attributes = []): RoomType
    {
        return RoomType::factory()->create(array_merge(['hotel_id' => $hotel->id], $attributes));
    }

    private function roomTypePayload(): array
    {
        return [
            'name'            => 'Phòng Deluxe Mới',
            'price_per_night' => 800000,
            'capacity'        => 2,
            'total_rooms'     => 5,
        ];
    }

    // ----------------------------------------------------------------
    // TC-RT-001 đến TC-RT-004: GET /admin/hotels/{hotelId}/room-types
    // ----------------------------------------------------------------

    public function test_admin_can_list_room_types(): void // TC-RT-001
    {
        $hotel = $this->makeHotel();
        $this->makeRoomType($hotel);

        $this->actingAs($this->makeUser('admin'))
            ->getJson("/api/v1/admin/hotels/{$hotel->id}/room-types")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_staff_can_list_room_types(): void // TC-RT-002
    {
        $hotel = $this->makeHotel();
        $this->makeRoomType($hotel);

        $this->actingAs($this->makeUser('staff'))
            ->getJson("/api/v1/admin/hotels/{$hotel->id}/room-types")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_list_room_types(): void // TC-RT-003
    {
        $hotel = $this->makeHotel();

        $this->actingAs($this->makeUser('customer'))
            ->getJson("/api/v1/admin/hotels/{$hotel->id}/room-types")
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_anonymous_cannot_list_room_types(): void // TC-RT-004
    {
        $hotel = $this->makeHotel();

        $this->getJson("/api/v1/admin/hotels/{$hotel->id}/room-types")
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    // ----------------------------------------------------------------
    // TC-RT-005 đến TC-RT-008: POST /admin/hotels/{hotelId}/room-types
    // ----------------------------------------------------------------

    public function test_admin_can_create_room_type(): void // TC-RT-005
    {
        $hotel = $this->makeHotel(['status' => 'active']);

        $this->actingAs($this->makeUser('admin'))
            ->postJson("/api/v1/admin/hotels/{$hotel->id}/room-types", $this->roomTypePayload())
            ->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_staff_can_create_room_type(): void // TC-RT-006
    {
        $hotel = $this->makeHotel(['status' => 'active']);

        $this->actingAs($this->makeUser('staff'))
            ->postJson("/api/v1/admin/hotels/{$hotel->id}/room-types", $this->roomTypePayload())
            ->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_create_room_type(): void // TC-RT-007
    {
        $hotel = $this->makeHotel(['status' => 'active']);

        $this->actingAs($this->makeUser('customer'))
            ->postJson("/api/v1/admin/hotels/{$hotel->id}/room-types", $this->roomTypePayload())
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_admin_cannot_create_room_type_for_hidden_hotel(): void // TC-RT-008
    {
        $hotel = $this->makeHotel(['status' => 'hidden']);

        $this->actingAs($this->makeUser('admin'))
            ->postJson("/api/v1/admin/hotels/{$hotel->id}/room-types", $this->roomTypePayload())
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('hotel_id');
    }

    // ----------------------------------------------------------------
    // TC-RT-009 đến TC-RT-011: GET /admin/room-types/{id}
    // ----------------------------------------------------------------

    public function test_admin_can_view_room_type_detail(): void // TC-RT-009
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('admin'))
            ->getJson("/api/v1/admin/room-types/{$roomType->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_staff_can_view_room_type_detail(): void // TC-RT-010
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('staff'))
            ->getJson("/api/v1/admin/room-types/{$roomType->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_view_room_type_detail(): void // TC-RT-011
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('customer'))
            ->getJson("/api/v1/admin/room-types/{$roomType->id}")
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    // ----------------------------------------------------------------
    // TC-RT-012 đến TC-RT-015: PUT /admin/room-types/{id}
    // ----------------------------------------------------------------

    public function test_admin_can_update_room_type(): void // TC-RT-012
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('admin'))
            ->putJson("/api/v1/admin/room-types/{$roomType->id}", ['name' => 'Tên Mới'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_staff_can_update_room_type(): void // TC-RT-013
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('staff'))
            ->putJson("/api/v1/admin/room-types/{$roomType->id}", ['name' => 'Tên Mới 2'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_update_room_type(): void // TC-RT-014
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('customer'))
            ->putJson("/api/v1/admin/room-types/{$roomType->id}", ['name' => 'Hack tên'])
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_staff_can_update_room_type_when_hotel_hidden(): void // TC-RT-015
    {
        $hotel    = $this->makeHotel(['status' => 'hidden']);
        $roomType = $this->makeRoomType($hotel);

        $this->actingAs($this->makeUser('staff'))
            ->putJson("/api/v1/admin/room-types/{$roomType->id}", ['name' => 'Sửa khi hotel ẩn'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // ----------------------------------------------------------------
    // TC-RT-016 đến TC-RT-018: DELETE /admin/room-types/{id}
    // ----------------------------------------------------------------

    public function test_admin_can_delete_room_type(): void // TC-RT-016
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('admin'))
            ->deleteJson("/api/v1/admin/room-types/{$roomType->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_staff_can_delete_room_type(): void // TC-RT-017
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('staff'))
            ->deleteJson("/api/v1/admin/room-types/{$roomType->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_delete_room_type(): void // TC-RT-018
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('customer'))
            ->deleteJson("/api/v1/admin/room-types/{$roomType->id}")
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    // ----------------------------------------------------------------
    // TC-RT-019 đến TC-RT-021: POST /admin/room-types/{id}/restore
    // ----------------------------------------------------------------

    public function test_admin_can_restore_room_type(): void // TC-RT-019
    {
        $roomType = $this->makeRoomType($this->makeHotel());
        $roomType->delete();

        $this->actingAs($this->makeUser('admin'))
            ->postJson("/api/v1/admin/room-types/{$roomType->id}/restore")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_staff_can_restore_room_type(): void // TC-RT-020
    {
        $roomType = $this->makeRoomType($this->makeHotel());
        $roomType->delete();

        $this->actingAs($this->makeUser('staff'))
            ->postJson("/api/v1/admin/room-types/{$roomType->id}/restore")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_restore_room_type(): void // TC-RT-021
    {
        $roomType = $this->makeRoomType($this->makeHotel());
        $roomType->delete();

        $this->actingAs($this->makeUser('customer'))
            ->postJson("/api/v1/admin/room-types/{$roomType->id}/restore")
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    // ----------------------------------------------------------------
    // TC-RT-022 đến TC-RT-024: PATCH /admin/room-types/{id}/price
    // ----------------------------------------------------------------

    public function test_admin_can_update_price(): void // TC-RT-022
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('admin'))
            ->patchJson("/api/v1/admin/room-types/{$roomType->id}/price", ['price_per_night' => 999000])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_staff_can_update_price(): void // TC-RT-023
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('staff'))
            ->patchJson("/api/v1/admin/room-types/{$roomType->id}/price", ['price_per_night' => 999000])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_update_price(): void // TC-RT-024
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('customer'))
            ->patchJson("/api/v1/admin/room-types/{$roomType->id}/price", ['price_per_night' => 999000])
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    // ----------------------------------------------------------------
    // TC-RT-025 đến TC-RT-027: PATCH /admin/room-types/{id}/inventory
    // ----------------------------------------------------------------

    public function test_admin_can_update_inventory(): void // TC-RT-025
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('admin'))
            ->patchJson("/api/v1/admin/room-types/{$roomType->id}/inventory", ['total_rooms' => 10])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_staff_can_update_inventory(): void // TC-RT-026
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('staff'))
            ->patchJson("/api/v1/admin/room-types/{$roomType->id}/inventory", ['total_rooms' => 10])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_customer_cannot_update_inventory(): void // TC-RT-027
    {
        $roomType = $this->makeRoomType($this->makeHotel());

        $this->actingAs($this->makeUser('customer'))
            ->patchJson("/api/v1/admin/room-types/{$roomType->id}/inventory", ['total_rooms' => 10])
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }
}
