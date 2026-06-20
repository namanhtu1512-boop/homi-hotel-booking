<?php

namespace Tests\Feature\AuditLog;

use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit log cơ bản — kiểm tra các thao tác quản trị nhạy cảm trên route Blade
 * có ghi nhận đúng vào bảng audit_logs.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    public function test_admin_toggling_user_status_creates_audit_log(): void
    {
        $admin  = $this->makeUser('admin');
        $target = $this->makeUser('customer');

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $target->id))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $admin->id,
            'action'         => 'user.status_toggled',
            'auditable_type' => 'users',
            'auditable_id'   => $target->id,
        ]);
    }

    public function test_updating_hotel_info_creates_audit_log(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->put(route('admin.hotel-info.update'), [
                'name'    => 'Homi Test Hotel',
                'address' => '1 Hàng Bài',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $admin->id,
            'action'         => 'hotel_info.updated',
            'auditable_type' => 'hotel_info',
            'auditable_id'   => 1,
        ]);
    }

    public function test_creating_room_type_creates_audit_log(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post(route('admin.room-types.store'), [
            'name'            => 'Deluxe',
            'price_per_night' => 500000,
            'capacity'        => 2,
            'total_rooms'     => 5,
        ])->assertRedirect();

        $roomType = RoomType::where('name', 'Deluxe')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $admin->id,
            'action'         => 'room_type.created',
            'auditable_type' => 'room_types',
            'auditable_id'   => $roomType->id,
        ]);
    }

    public function test_updating_room_type_creates_audit_log(): void
    {
        $admin    = $this->makeUser('admin');
        $roomType = RoomType::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.room-types.update', $roomType->id), [
                'name'            => 'Tên Mới',
                'price_per_night' => 999000,
                'capacity'        => 2,
                'total_rooms'     => 5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'room_type.updated',
            'auditable_type' => 'room_types',
            'auditable_id'   => $roomType->id,
        ]);
    }

    public function test_deleting_room_type_creates_audit_log(): void
    {
        $admin    = $this->makeUser('admin');
        $roomType = RoomType::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.room-types.destroy', $roomType->id))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'room_type.deleted',
            'auditable_type' => 'room_types',
            'auditable_id'   => $roomType->id,
        ]);
    }

    public function test_staff_cannot_toggle_other_user_status(): void
    {
        $staff  = $this->makeUser('staff');
        $target = $this->makeUser('customer');

        $this->actingAs($staff)
            ->patch(route('admin.users.toggle-status', $target->id))
            ->assertForbidden();
    }
}
