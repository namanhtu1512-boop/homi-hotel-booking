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

    public function test_customer_can_access_customer_dashboard(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->get('/customer/dashboard')
            ->assertOk();
    }

    public function test_customer_cannot_access_admin_area(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_staff_can_access_admin_dashboard(): void
    {
        $user = User::factory()->staff()->create();

        $this->actingAs($user)
            ->withSession(['login_context' => 'admin'])
            ->get('/admin/dashboard')
            ->assertOk();
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
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_only_admin_can_toggle_user_status(): void
    {
        $staff  = User::factory()->staff()->create();
        $target = User::factory()->customer()->create();

        $this->actingAs($staff)
            ->patch("/admin/users/{$target->id}/toggle-status")
            ->assertRedirect(route('admin.login'));
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
