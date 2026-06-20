<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test case đăng ký qua form Blade (/customer/register).
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_success_redirects_to_customer_dashboard(): void
    {
        $response = $this->post('/customer/register', [
            'name'                  => 'Nguyễn Văn A',
            'email'                 => 'test@homi.vn',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'test@homi.vn',
            'role'  => 'customer',
        ]);
    }

    public function test_register_form_has_required_fields(): void
    {
        // Khóa lại bug cũ: register.blade.php từng là bản copy của login.blade.php
        // (thiếu hẳn field name/password_confirmation).
        $this->get('/customer/register')
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@homi.vn']);

        $this->post('/customer/register', [
            'name'                  => 'Người Trùng',
            'email'                 => 'dup@homi.vn',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ])->assertSessionHasErrors('email');
    }

    public function test_register_fails_when_required_fields_missing(): void
    {
        $this->post('/customer/register', [])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_register_fails_when_password_confirmation_mismatch(): void
    {
        $this->post('/customer/register', [
            'name'                  => 'Test',
            'email'                 => 'mismatch@homi.vn',
            'password'              => '12345678',
            'password_confirmation' => 'wrong',
        ])->assertSessionHasErrors('password');
    }

    public function test_register_fails_when_password_too_short(): void
    {
        $this->post('/customer/register', [
            'name'                  => 'Test',
            'email'                 => 'short@homi.vn',
            'password'              => '123',
            'password_confirmation' => '123',
        ])->assertSessionHasErrors('password');
    }
}
