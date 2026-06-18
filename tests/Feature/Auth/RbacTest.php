<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test case theo docs/test-auth.md mục 9 — TC-RBAC-01 đến TC-RBAC-07.
 */
class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_access_own_profile(): void // TC-RBAC-01
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/me')
            ->assertStatus(200)
            ->assertJsonPath('data.user.role', 'customer');
    }

    public function test_staff_can_access_own_profile(): void // TC-RBAC-02
    {
        $user = User::factory()->staff()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/me')
            ->assertJsonPath('data.user.role', 'staff');
    }

    public function test_admin_can_access_own_profile(): void // TC-RBAC-03
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/me')
            ->assertJsonPath('data.user.role', 'admin');
    }

    public function test_inactive_account_cannot_login(): void // TC-RBAC-04
    {
        User::factory()->locked()->create([
            'email'    => 'locked@homi.vn',
            'password' => '123456',
        ]);

        $this->postJson('/api/v1/login', [
            'email'    => 'locked@homi.vn',
            'password' => '123456',
        ])->assertStatus(403);
    }

    public function test_unauthenticated_request_returns_401(): void // TC-RBAC-05
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
        $this->putJson('/api/v1/profile', [])->assertStatus(401);
        $this->putJson('/api/v1/change-password', [])->assertStatus(401);
        $this->postJson('/api/v1/logout')->assertStatus(401);
    }

    public function test_newly_registered_user_has_customer_role(): void // TC-RBAC-06
    {
        $response = $this->postJson('/api/v1/register', [
            'name'                  => 'Người Mới',
            'email'                 => 'moi@homi.vn',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertJsonPath('data.user.role', 'customer');
    }

    public function test_revoked_token_returns_401(): void // TC-RBAC-07
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $user->tokens()->delete();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me')
            ->assertStatus(401);
    }
}
