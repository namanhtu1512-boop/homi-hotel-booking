<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test case theo docs/test-auth.md mục 4 — TC-LOGIN-01 đến TC-LOGIN-06.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success_returns_token(): void // TC-LOGIN-01
    {
        User::factory()->create([
            'email'    => 'user@homi.vn',
            'password' => '123456',
            'status'   => 'active',
        ]);

        $this->postJson('/api/v1/login', [
            'email'    => 'user@homi.vn',
            'password' => '123456',
        ])->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_fails_with_wrong_password(): void // TC-LOGIN-02
    {
        User::factory()->create(['email' => 'user@homi.vn', 'password' => '123456']);

        $this->postJson('/api/v1/login', [
            'email'    => 'user@homi.vn',
            'password' => 'sai_mat_khau',
        ])->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_login_fails_with_nonexistent_email(): void // TC-LOGIN-03
    {
        $this->postJson('/api/v1/login', [
            'email'    => 'khongtontai@homi.vn',
            'password' => '123456',
        ])->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_login_fails_when_account_is_locked(): void // TC-LOGIN-04
    {
        User::factory()->create([
            'email'    => 'user@homi.vn',
            'password' => '123456',
            'status'   => 'locked',
        ]);

        $this->postJson('/api/v1/login', [
            'email'    => 'user@homi.vn',
            'password' => '123456',
        ])->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_login_fails_when_fields_missing(): void // TC-LOGIN-05
    {
        $this->postJson('/api/v1/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_password_not_in_login_response(): void // TC-LOGIN-06
    {
        User::factory()->create(['email' => 'user@homi.vn', 'password' => '123456']);

        $response = $this->postJson('/api/v1/login', [
            'email'    => 'user@homi.vn',
            'password' => '123456',
        ]);

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('password', $response->json('data.user'));
    }
}
