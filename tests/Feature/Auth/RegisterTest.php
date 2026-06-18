<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test case theo docs/test-auth.md mục 3 — TC-REG-01 đến TC-REG-08.
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_success_with_full_data(): void // TC-REG-01
    {
        $response = $this->postJson('/api/v1/register', [
            'name'                  => 'Nguyễn Văn A',
            'email'                 => 'test@homi.vn',
            'phone'                 => '0901234567',
            'address'               => 'Hà Nội',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@homi.vn',
            'role'  => 'customer',
        ]);
    }

    public function test_register_success_without_optional_fields(): void // TC-REG-02
    {
        $this->postJson('/api/v1/register', [
            'name'                  => 'Nguyễn Văn B',
            'email'                 => 'b@homi.vn',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ])->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_register_fails_with_duplicate_email(): void // TC-REG-03
    {
        User::factory()->create(['email' => 'dup@homi.vn']);

        $this->postJson('/api/v1/register', [
            'name'                  => 'Người Trùng',
            'email'                 => 'dup@homi.vn',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ])->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('email');
    }

    public function test_register_fails_when_required_fields_missing(): void // TC-REG-04
    {
        $this->postJson('/api/v1/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_fails_with_invalid_email_format(): void // TC-REG-05
    {
        $this->postJson('/api/v1/register', [
            'name'                  => 'Test',
            'email'                 => 'not-an-email',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_fails_when_password_confirmation_mismatch(): void // TC-REG-06
    {
        $this->postJson('/api/v1/register', [
            'name'                  => 'Test',
            'email'                 => 'mismatch@homi.vn',
            'password'              => '123456',
            'password_confirmation' => 'wrong',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_register_fails_when_password_too_short(): void // TC-REG-07
    {
        $this->postJson('/api/v1/register', [
            'name'                  => 'Test',
            'email'                 => 'short@homi.vn',
            'password'              => '123',
            'password_confirmation' => '123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_password_not_exposed_in_response(): void // TC-REG-08
    {
        $response = $this->postJson('/api/v1/register', [
            'name'                  => 'Test An Toàn',
            'email'                 => 'safe@homi.vn',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertStatus(201);
        $this->assertArrayNotHasKey('password', $response->json('data.user'));
    }
}
