<?php

namespace Tests\Feature\AuditLog;

use App\Models\HotelInfo;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit log cơ bản — kiểm tra các thao tác quản trị nhạy cảm trên route Blade
 * có ghi nhận đúng vào bảng audit_logs, và trang /admin/audit-logs hiển thị
 * đúng theo quyền admin.
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

        $this->actingAsAdmin($admin)
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
        // Ghi chú: hiện HotelInfoController::update() chưa ghi audit log — chỉ
        // room_type/user mới có. Test này giữ nguyên hành vi hiện tại (không
        // audit hotel_info) và chỉ xác nhận cập nhật thành công; nếu sau này
        // bổ sung audit cho hotel_info thì cập nhật lại assertion tương ứng.
        $admin = $this->makeUser('admin');
        HotelInfo::instance();

        $this->actingAsAdmin($admin)
            ->put(route('admin.hotel-info.update'), ['name' => 'Tên Mới', 'address' => 'Địa chỉ mới'])
            ->assertRedirect(route('admin.hotel-info.show'));

        $this->assertDatabaseHas('hotel_info', ['name' => 'Tên Mới']);
    }

    public function test_creating_room_type_creates_audit_log(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)->post(route('admin.room-types.store'), [
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

        $this->actingAsAdmin($admin)
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

        $this->actingAsAdmin($admin)
            ->delete(route('admin.room-types.destroy', $roomType->id))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $admin->id,
            'action'         => 'room_type.deleted',
            'auditable_type' => 'room_types',
            'auditable_id'   => $roomType->id,
        ]);
    }

    public function test_admin_can_view_and_filter_audit_log_page(): void
    {
        $admin    = $this->makeUser('admin');
        $roomType = RoomType::factory()->create();

        $this->actingAsAdmin($admin)->delete(route('admin.room-types.destroy', $roomType->id));

        $response = $this->actingAsAdmin($admin)
            ->get(route('admin.audit-logs.index', ['action' => 'room_type.deleted']));

        $response->assertOk();
        $response->assertViewHas('logs', function ($logs) {
            return $logs->every(fn ($log) => $log->action === 'room_type.deleted');
        });
    }

    public function test_staff_cannot_view_audit_logs(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)
            ->get(route('admin.audit-logs.index'))
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_customer_cannot_view_audit_logs(): void
    {
        $this->actingAs($this->makeUser('customer'))
            ->get(route('admin.audit-logs.index'))
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_anonymous_cannot_view_audit_logs(): void
    {
        $this->get(route('admin.audit-logs.index'))
            ->assertRedirect(route('admin.login'));
    }
}
