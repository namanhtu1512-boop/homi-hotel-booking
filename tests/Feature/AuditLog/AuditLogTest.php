<?php

namespace Tests\Feature\AuditLog;

use App\Models\HotelInfo;
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
            ->withSession(['login_context' => 'admin'])
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
        $hotel = HotelInfo::instance();

        $this->actingAs($admin)
            ->putJson('/api/v1/admin/hotel-info', ['name' => 'Tên Mới'])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'hotel_info.updated',
            'auditable_type' => 'hotel_info',
            'auditable_id'   => $hotel->id,
        ]);
    }

    public function test_toggling_hotel_info_status_creates_audit_log(): void
    {
        $admin = $this->makeUser('admin');
        $hotel = HotelInfo::instance();
        $hotel->update(['status' => 'active']);

        $this->actingAs($admin)
            ->patchJson('/api/v1/admin/hotel-info/toggle-maintenance')
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'hotel_info.status_toggled',
            'auditable_type' => 'hotel_info',
            'auditable_id'   => $hotel->id,
        ]);
    }

    public function test_creating_room_type_creates_audit_log(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/room-types', [
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
            ->withSession(['login_context' => 'admin'])
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
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->putJson('/api/v1/admin/hotel-info', ['name' => 'Homi Cập Nhật']);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/audit-logs')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['logs', 'meta']])
            ->assertJsonFragment(['action' => 'hotel_info.updated']);
    }

    public function test_staff_cannot_toggle_other_user_status(): void
    {
        $staff  = $this->makeUser('staff');
        $target = $this->makeUser('customer');

        $this->actingAs($staff)
            ->patchJson("/api/v1/admin/users/{$target->id}/toggle-status")
            ->assertStatus(403);
    }

    public function test_customer_cannot_view_audit_logs(): void
    {
        $this->actingAs($this->makeUser('customer'))
            ->getJson('/api/v1/admin/audit-logs')
            ->assertStatus(403);
    }

    public function test_anonymous_cannot_view_audit_logs(): void
    {
        $this->getJson('/api/v1/admin/audit-logs')->assertStatus(401);
    }

    public function test_audit_logs_can_be_filtered_by_action(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->patchJson('/api/v1/admin/hotel-info/toggle-maintenance');
        $this->actingAs($admin)->putJson('/api/v1/admin/hotel-info', ['name' => 'Homi Đổi Tên']);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/audit-logs?action=hotel_info.updated');

        $response->assertStatus(200);
        $actions = collect($response->json('data.logs'))->pluck('action')->unique();
        $this->assertEquals(['hotel_info.updated'], $actions->values()->all());
    }
}
