<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test case theo docs/test-auth.md mục 5–8 — Logout, Me, Update Profile, Change Password.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Logout — TC-LOGOUT-01, 02
    // ----------------------------------------------------------------

    public function test_logout_success(): void // TC-LOGOUT-01
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/logout')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_requires_authentication(): void // TC-LOGOUT-02
    {
        $this->postJson('/api/v1/logout')->assertStatus(401);
    }

    // ----------------------------------------------------------------
    // Me — TC-ME-01, 02
    // ----------------------------------------------------------------

    public function test_me_returns_authenticated_user(): void // TC-ME-01
    {
        $user = User::factory()->create(['email' => 'me@homi.vn']);

        $this->actingAs($user)
            ->getJson('/api/v1/me')
            ->assertStatus(200)
            ->assertJsonPath('data.user.email', 'me@homi.vn');
    }

    public function test_me_requires_authentication(): void // TC-ME-02
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    // ----------------------------------------------------------------
    // Update profile — TC-PROFILE-01 đến 04
    // ----------------------------------------------------------------

    public function test_update_profile_success(): void // TC-PROFILE-01
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/v1/profile', [
                'name'    => 'Tên Mới',
                'email'   => 'new@homi.vn',
                'phone'   => '0909090909',
                'address' => 'TP HCM',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.user.name', 'Tên Mới')
            ->assertJsonPath('data.user.email', 'new@homi.vn');

        $this->assertDatabaseHas('users', ['email' => 'new@homi.vn']);
    }

    public function test_update_profile_fails_when_required_fields_missing(): void // TC-PROFILE-02
    {
        $user = User::factory()->create();

        // 'email' dùng rule 'sometimes', chỉ validate khi key có mặt trong request.
        $this->actingAs($user)
            ->putJson('/api/v1/profile', ['email' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_update_profile_fails_when_email_taken_by_another_user(): void // TC-PROFILE-03
    {
        User::factory()->create(['email' => 'taken@homi.vn']);
        $userB = User::factory()->create();

        $this->actingAs($userB)
            ->putJson('/api/v1/profile', [
                'name'  => 'Test',
                'email' => 'taken@homi.vn',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_update_profile_allows_keeping_same_email(): void // TC-PROFILE-04
    {
        $user = User::factory()->create(['email' => 'user@homi.vn']);

        $this->actingAs($user)
            ->putJson('/api/v1/profile', [
                'name'  => 'New Name',
                'email' => 'user@homi.vn',
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // ----------------------------------------------------------------
    // Change password — TC-CHPW-01 đến 03
    // ----------------------------------------------------------------

    public function test_change_password_success(): void // TC-CHPW-01
    {
        $user = User::factory()->create(['password' => '123456']);

        $this->actingAs($user)
            ->putJson('/api/v1/change-password', [
                'current_password'     => '123456',
                'password'              => 'newpass123',
                'password_confirmation' => 'newpass123',
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpass123', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_change_password_fails_with_wrong_current_password(): void // TC-CHPW-02
    {
        $user = User::factory()->create(['password' => '123456']);

        $this->actingAs($user)
            ->putJson('/api/v1/change-password', [
                'current_password'     => 'sai_roi',
                'password'              => 'newpass123',
                'password_confirmation' => 'newpass123',
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_change_password_fails_when_confirmation_mismatch(): void // TC-CHPW-03
    {
        $user = User::factory()->create(['password' => '123456']);

        $this->actingAs($user)
            ->putJson('/api/v1/change-password', [
                'current_password'     => '123456',
                'password'              => 'newpass123',
                'password_confirmation' => 'khac',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }
}
