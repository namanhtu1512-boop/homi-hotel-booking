<?php

namespace Tests\Feature\RoomType;

use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test — kiểm tra phân quyền và CRUD Blade /admin/room-types.
 */
class AdminRoomTypeAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function makeRoomType(array $attributes = []): RoomType
    {
        return RoomType::factory()->create($attributes);
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

    public function test_admin_can_list_room_types(): void
    {
        $this->makeRoomType();

        $this->actingAs($this->makeUser('admin'))
            ->get(route('admin.room-types.index'))
            ->assertOk()
            ->assertViewIs('admin.room-types.index');
    }

    public function test_customer_cannot_list_room_types(): void
    {
        $this->actingAs($this->makeUser('customer'))
            ->get(route('admin.room-types.index'))
            ->assertForbidden();
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get(route('admin.room-types.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_create_room_type(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->post(route('admin.room-types.store'), $this->roomTypePayload())
            ->assertRedirect(route('admin.room-types.index'));

        $this->assertDatabaseHas('room_types', ['name' => 'Phòng Deluxe Mới']);
    }

    public function test_staff_can_create_room_type(): void
    {
        $this->actingAs($this->makeUser('staff'))
            ->post(route('admin.room-types.store'), $this->roomTypePayload())
            ->assertRedirect(route('admin.room-types.index'));
    }

    public function test_customer_cannot_create_room_type(): void
    {
        $this->actingAs($this->makeUser('customer'))
            ->post(route('admin.room-types.store'), $this->roomTypePayload())
            ->assertForbidden();
    }

    public function test_admin_can_update_room_type(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->makeUser('admin'))
            ->put(route('admin.room-types.update', $roomType->id), array_merge(
                $this->roomTypePayload(),
                ['name' => 'Tên Mới']
            ))
            ->assertRedirect(route('admin.room-types.index'));

        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'name' => 'Tên Mới']);
    }

    public function test_customer_cannot_update_room_type(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->makeUser('customer'))
            ->put(route('admin.room-types.update', $roomType->id), $this->roomTypePayload())
            ->assertForbidden();
    }

    public function test_admin_can_delete_room_type(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('admin.room-types.destroy', $roomType->id))
            ->assertRedirect(route('admin.room-types.index'));

        $this->assertSoftDeleted('room_types', ['id' => $roomType->id]);
    }

    public function test_customer_cannot_delete_room_type(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->makeUser('customer'))
            ->delete(route('admin.room-types.destroy', $roomType->id))
            ->assertForbidden();
    }

    public function test_admin_can_restore_room_type(): void
    {
        $roomType = $this->makeRoomType();
        $roomType->delete();

        $this->actingAs($this->makeUser('admin'))
            ->post(route('admin.room-types.restore', $roomType->id))
            ->assertRedirect(route('admin.room-types.index'));

        $this->assertNotSoftDeleted('room_types', ['id' => $roomType->id]);
    }

    public function test_admin_can_toggle_status(): void
    {
        $roomType = $this->makeRoomType(['status' => 'active']);

        $this->actingAs($this->makeUser('admin'))
            ->patch(route('admin.room-types.toggle-status', $roomType->id))
            ->assertRedirect(route('admin.room-types.index'));

        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'status' => 'hidden']);
    }
}
