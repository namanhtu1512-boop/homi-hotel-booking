<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/login';

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

    // TC-LOGIN-01: Đăng nhập thành công
    public function test_login_success_returns_token(): void
    {
        $this->createUser();

        $response = $this->postJson($this->url, [
            'email'    => 'user@homi.vn',
            'password' => '123456',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure([
                     'data' => ['user', 'token'],
                 ]);
    }

    // TC-LOGIN-02: Đăng nhập thất bại - sai mật khẩu
    public function test_login_fails_with_wrong_password(): void
    {
        $this->createUser();

        $response = $this->postJson($this->url, [
            'email'    => 'user@homi.vn',
            'password' => 'sai_mat_khau',
        ]);

        $response->assertStatus(401)
                 ->assertJsonPath('success', false);
    }

    // TC-LOGIN-03: Đăng nhập thất bại - email không tồn tại
    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson($this->url, [
            'email'    => 'khongtontai@homi.vn',
            'password' => '123456',
        ]);

        $response->assertStatus(401)
                 ->assertJsonPath('success', false);
    }

    // TC-LOGIN-04: Đăng nhập thất bại - tài khoản bị khóa
    public function test_login_fails_when_account_is_locked(): void
    {
        $this->createUser(['status' => 'locked']);

        $response = $this->postJson($this->url, [
            'email'    => 'user@homi.vn',
            'password' => '123456',
        ]);

        $response->assertStatus(403)
                 ->assertJsonPath('success', false);
    }

    // TC-LOGIN-05: Đăng nhập thất bại - thiếu trường bắt buộc
    public function test_login_fails_when_fields_missing(): void
    {
        $response = $this->postJson($this->url, []);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    // TC-LOGIN-06: Token không chứa mật khẩu
    public function test_password_not_in_login_response(): void
    {
        $this->createUser();

        $response = $this->postJson($this->url, [
            'email'    => 'user@homi.vn',
            'password' => '123456',
        ]);

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('password', $response->json('data.user'));
    }
}
