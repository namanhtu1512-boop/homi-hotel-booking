<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test case đăng nhập qua form Blade (/customer/login và /admin/login).
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_login_redirects_to_customer_dashboard(): void
    {
        User::factory()->customer()->create([
            'email'    => 'user@homi.vn',
            'password' => '123456',
            'status'   => 'active',
        ]);

        $this->post('/customer/login', [
            'email'    => 'user@homi.vn',
            'password' => '123456',
        ])->assertRedirect(route('customer.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        User::factory()->admin()->create([
            'email'    => 'admin@homi.vn',
            'password' => '123456',
            'status'   => 'active',
        ]);

        $this->post('/admin/login', [
            'email'    => 'admin@homi.vn',
            'password' => '123456',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'user@homi.vn', 'password' => '123456']);

        $this->post('/customer/login', [
            'email'    => 'user@homi.vn',
            'password' => 'sai_mat_khau',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $this->post('/customer/login', [
            'email'    => 'khongtontai@homi.vn',
            'password' => '123456',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_fails_when_account_is_locked(): void
    {
        User::factory()->create([
            'email'    => 'user@homi.vn',
            'password' => '123456',
            'status'   => 'locked',
        ]);

        $this->post('/customer/login', [
            'email'    => 'user@homi.vn',
            'password' => '123456',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_fails_when_fields_missing(): void
    {
        $this->post('/customer/login', [])->assertSessionHasErrors(['email', 'password']);
    }

    /**
     * Đối xứng với adminLogin() chặn customer đăng nhập nhầm qua form admin —
     * admin/staff đăng nhập nhầm qua form khách hàng cũng phải bị chặn rõ
     * ràng ngay tại đây, không để RoleMiddleware dội qua dội lại.
     */
    public function test_admin_cannot_login_through_customer_form(): void
    {
        User::factory()->admin()->create([
            'email'    => 'admin@homi.vn',
            'password' => '123456',
        ]);

        $this->post('/customer/login', [
            'email'    => 'admin@homi.vn',
            'password' => '123456',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_staff_cannot_login_through_customer_form(): void
    {
        User::factory()->staff()->create([
            'email'    => 'staff@homi.vn',
            'password' => '123456',
        ]);

        $this->post('/customer/login', [
            'email'    => 'staff@homi.vn',
            'password' => '123456',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
