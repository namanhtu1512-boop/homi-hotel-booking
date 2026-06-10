<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name'     => 'Test User',
            'email'    => 'user@homi.vn',
            'password' => Hash::make('123456'),
            'role'     => 'customer',
            'status'   => 'active',
        ], $overrides));
    }

    // --- ME ---

    // TC-ME-01: Xem thông tin tài khoản thành công
    public function test_me_returns_authenticated_user(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->getJson('/api/v1/me');

        $response->assertStatus(200)
                 ->assertJsonPath('data.user.email', 'user@homi.vn');
    }

    // TC-ME-02: Không thể xem thông tin khi chưa đăng nhập
    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    // --- UPDATE PROFILE ---

    // TC-PROFILE-01: Cập nhật hồ sơ thành công
    public function test_update_profile_success(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->putJson('/api/v1/profile', [
            'name'    => 'Tên Mới',
            'email'   => 'new@homi.vn',
            'phone'   => '0909090909',
            'address' => 'TP HCM',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.user.name', 'Tên Mới')
                 ->assertJsonPath('data.user.email', 'new@homi.vn');

        $this->assertDatabaseHas('users', ['email' => 'new@homi.vn']);
    }

    // TC-PROFILE-02: Cập nhật thất bại - thiếu trường bắt buộc
    public function test_update_profile_fails_when_required_fields_missing(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->putJson('/api/v1/profile', []);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['name', 'email']]);
    }

    // TC-PROFILE-03: Cập nhật thất bại - email trùng với user khác
    public function test_update_profile_fails_when_email_taken_by_another_user(): void
    {
        $this->createUser(['email' => 'taken@homi.vn']);
        $user = $this->createUser(['email' => 'mine@homi.vn']);

        $response = $this->actingAs($user)->putJson('/api/v1/profile', [
            'name'  => 'Test',
            'email' => 'taken@homi.vn',
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['email']]);
    }

    // TC-PROFILE-04: Cập nhật thành công với chính email hiện tại (không bị lỗi unique)
    public function test_update_profile_allows_keeping_same_email(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->putJson('/api/v1/profile', [
            'name'  => 'New Name',
            'email' => 'user@homi.vn',
        ]);

        $response->assertStatus(200);
    }

    // --- CHANGE PASSWORD ---

    // TC-CHPW-01: Đổi mật khẩu thành công
    public function test_change_password_success(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->putJson('/api/v1/change-password', [
            'current_password'      => '123456',
            'password'              => 'newpass123',
            'password_confirmation' => 'newpass123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        // Xác nhận mật khẩu đã thay đổi trong DB
        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    // TC-CHPW-02: Đổi mật khẩu thất bại - mật khẩu hiện tại sai
    public function test_change_password_fails_with_wrong_current_password(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->putJson('/api/v1/change-password', [
            'current_password'      => 'sai_roi',
            'password'              => 'newpass123',
            'password_confirmation' => 'newpass123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // TC-CHPW-03: Đổi mật khẩu thất bại - xác nhận mật khẩu không khớp
    public function test_change_password_fails_when_confirmation_mismatch(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->putJson('/api/v1/change-password', [
            'current_password'      => '123456',
            'password'              => 'newpass123',
            'password_confirmation' => 'khac',
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['password']]);
    }

    // --- LOGOUT ---

    // TC-LOGOUT-01: Đăng xuất thành công
    public function test_logout_success(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->postJson('/api/v1/logout');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    // TC-LOGOUT-02: Không thể đăng xuất khi chưa đăng nhập
    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/v1/logout')->assertStatus(401);
    }
}
