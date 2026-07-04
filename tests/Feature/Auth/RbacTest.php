<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test case phân quyền customer/staff/admin trên route Blade /customer/* và /admin/*.
 */
class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_dashboard_redirects_to_home(): void
    {
        // Không còn trang dashboard riêng — route giữ lại chỉ để chuyển
        // hướng về trang chủ (nơi các chức năng đã được tích hợp vào nav).
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->get('/customer/dashboard')
            ->assertRedirect(route('home'));
    }

    public function test_customer_cannot_access_admin_area(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_staff_can_access_staff_dashboard(): void
    {
        $user = User::factory()->staff()->create();

        $this->actingAs($user)
            ->withSession(['login_context' => 'admin'])
            ->get('/staff/dashboard')
            ->assertOk();
    }

    public function test_staff_cannot_access_admin_area_at_all(): void
    {
        // Staff dùng khu vực /staff/* riêng — không còn quyền vào /admin/*
        // dù chỉ là dashboard (khác với trước đây khi dùng chung route).
        $user = User::factory()->staff()->create();

        $this->actingAs($user)
            ->withSession(['login_context' => 'admin'])
            ->get('/admin/dashboard')
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_admin_cannot_access_staff_area(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->withSession(['login_context' => 'admin'])
            ->get('/staff/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->withSession(['login_context' => 'admin'])
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_staff_cannot_access_customer_area(): void
    {
        $user = User::factory()->staff()->create();

        $this->actingAs($user)
            ->get('/customer/dashboard')
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_staff_cannot_manage_users(): void
    {
        $staff  = User::factory()->staff()->create();
        $target = User::factory()->customer()->create();

        // Đã đăng nhập đúng ngữ cảnh admin nhưng vai trò staff → bị chặn về
        // dashboard riêng của staff (users chỉ nằm trong /admin/*).
        $this->actingAs($staff)
            ->withSession(['login_context' => 'admin'])
            ->get('/admin/users')
            ->assertRedirect(route('staff.dashboard'));

        $this->actingAs($staff)
            ->withSession(['login_context' => 'admin'])
            ->patch("/admin/users/{$target->id}/toggle-status")
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_admin_can_manage_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->withSession(['login_context' => 'admin'])
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_staff_cannot_delete_room_type_via_web(): void
    {
        // Xóa loại phòng chỉ tồn tại ở /admin/* — staff không có route này
        // (Staff\RoomTypeController không có method destroy), nên chặn
        // ngay từ tầng route/middleware trước khi tới action bất kỳ.
        $staff    = User::factory()->staff()->create();
        $roomType = \App\Models\RoomType::factory()->create();

        $this->actingAs($staff)
            ->withSession(['login_context' => 'admin'])
            ->delete(route('admin.room-types.destroy', $roomType->id))
            ->assertRedirect(route('staff.dashboard'));

        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'deleted_at' => null]);
    }

    public function test_staff_cannot_edit_hotel_info(): void
    {
        // Sửa thông tin khách sạn chỉ tồn tại ở /admin/* — staff chỉ có
        // route xem (staff.hotel-info.show), không có edit/update.
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->withSession(['login_context' => 'admin'])
            ->get(route('admin.hotel-info.edit'))
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_staff_can_access_own_staff_pages(): void
    {
        $staff = User::factory()->staff()->create();

        // Nhân viên có khu vực /staff/* riêng — vẫn xem được thông tin
        // khách sạn, loại phòng, đơn đặt phòng, nhưng qua route/view riêng.
        $this->actingAs($staff)->withSession(['login_context' => 'admin'])
            ->get(route('staff.hotel-info.show'))->assertOk();
        $this->actingAs($staff)->withSession(['login_context' => 'admin'])
            ->get(route('staff.room-types.index'))->assertOk();
        $this->actingAs($staff)->withSession(['login_context' => 'admin'])
            ->get(route('staff.bookings.index'))->assertOk();
        $this->actingAs($staff)->withSession(['login_context' => 'admin'])
            ->get(route('staff.payments.index'))->assertOk();
    }

    public function test_locked_account_cannot_login(): void
    {
        User::factory()->locked()->create([
            'email'    => 'locked@homi.vn',
            'password' => '123456',
        ]);

        $this->post('/customer/login', [
            'email'    => 'locked@homi.vn',
            'password' => '123456',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $this->get('/customer/dashboard')->assertRedirect(route('login'));
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
    }

    public function test_newly_registered_user_has_customer_role(): void
    {
        $this->post('/customer/register', [
            'name'                  => 'Người Mới',
            'email'                 => 'moi@homi.vn',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'moi@homi.vn', 'role' => 'customer']);
    }
}
