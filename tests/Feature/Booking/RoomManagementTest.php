<?php

namespace Tests\Feature\Booking;

use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quản lý phòng vật lý (module lễ tân/buồng phòng nội bộ) — admin CRUD
 * đầy đủ, staff chỉ đổi trạng thái dọn phòng — mirror phân quyền RoomType.
 */
class RoomManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    public function test_admin_can_view_rooms_list(): void
    {
        $admin = $this->makeUser('admin');
        Room::factory()->create();

        $this->actingAsAdmin($admin)->get('/admin/rooms')->assertOk();
    }

    public function test_admin_can_create_room(): void
    {
        $admin    = $this->makeUser('admin');
        $roomType = RoomType::factory()->create();

        $this->actingAsAdmin($admin)
            ->post('/admin/rooms', ['room_type_id' => $roomType->id, 'room_number' => '501'])
            ->assertRedirect(route('admin.rooms.index'));

        $this->assertDatabaseHas('rooms', ['room_number' => '501', 'room_type_id' => $roomType->id]);
    }

    public function test_admin_cannot_create_room_with_duplicate_number(): void
    {
        $admin    = $this->makeUser('admin');
        $roomType = RoomType::factory()->create();
        Room::factory()->create(['room_number' => '501']);

        $this->actingAsAdmin($admin)
            ->post('/admin/rooms', ['room_type_id' => $roomType->id, 'room_number' => '501'])
            ->assertSessionHasErrors('room_number');
    }

    public function test_admin_can_update_room(): void
    {
        $admin = $this->makeUser('admin');
        $room  = Room::factory()->create(['room_number' => '501']);

        $this->actingAsAdmin($admin)
            ->put("/admin/rooms/{$room->id}", ['room_type_id' => $room->room_type_id, 'room_number' => '502'])
            ->assertRedirect(route('admin.rooms.index'));

        $this->assertSame('502', $room->fresh()->room_number);
    }

    public function test_admin_can_delete_room(): void
    {
        $admin = $this->makeUser('admin');
        $room  = Room::factory()->create();

        $this->actingAsAdmin($admin)
            ->delete("/admin/rooms/{$room->id}")
            ->assertRedirect(route('admin.rooms.index'));

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_admin_can_update_housekeeping_status(): void
    {
        $admin = $this->makeUser('admin');
        $room  = Room::factory()->create(['housekeeping_status' => 'clean']);

        $this->actingAsAdmin($admin)
            ->patch("/admin/rooms/{$room->id}/housekeeping", ['housekeeping_status' => 'dirty'])
            ->assertRedirect(route('admin.rooms.index'));

        $this->assertSame('dirty', $room->fresh()->housekeeping_status);
    }

    public function test_staff_can_view_rooms_list_and_update_housekeeping_status(): void
    {
        $staff = $this->makeUser('staff');
        $room  = Room::factory()->create(['housekeeping_status' => 'clean']);

        $this->actingAsAdmin($staff)->get('/staff/rooms')->assertOk();

        $this->actingAsAdmin($staff)
            ->patch("/staff/rooms/{$room->id}/housekeeping", ['housekeeping_status' => 'inspected'])
            ->assertRedirect(route('staff.rooms.index'));

        $this->assertSame('inspected', $room->fresh()->housekeeping_status);
    }

    public function test_staff_cannot_access_admin_rooms(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAsAdmin($staff)
            ->get('/admin/rooms')
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_customer_cannot_access_admin_or_staff_rooms(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)->get('/admin/rooms')->assertRedirect(route('customer.dashboard'));
        $this->actingAs($customer)->get('/staff/rooms')->assertRedirect(route('customer.dashboard'));
    }
}
