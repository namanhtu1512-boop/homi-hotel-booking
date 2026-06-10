<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/register';

    // TC-REG-01: Đăng ký thành công với đầy đủ thông tin hợp lệ
    public function test_register_success_with_full_data(): void
    {
        $response = $this->postJson($this->url, [
            'name'                  => 'Nguyễn Văn A',
            'email'                 => 'test@homi.vn',
            'phone'                 => '0901234567',
            'address'               => 'Hà Nội',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure([
                     'success', 'message',
                     'data' => ['user', 'token'],
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@homi.vn',
            'role'  => 'customer',
        ]);
    }

    // TC-REG-02: Đăng ký thành công không cần phone/address
    public function test_register_success_without_optional_fields(): void
    {
        $response = $this->postJson($this->url, [
            'name'                  => 'Nguyễn Văn B',
            'email'                 => 'b@homi.vn',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);
    }

    // TC-REG-03: Đăng ký thất bại - email đã tồn tại
    public function test_register_fails_with_duplicate_email(): void
    {
        $this->postJson($this->url, [
            'name'                  => 'User 1',
            'email'                 => 'dup@homi.vn',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ]);

        $response = $this->postJson($this->url, [
            'name'                  => 'User 2',
            'email'                 => 'dup@homi.vn',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false)
                 ->assertJsonStructure(['errors' => ['email']]);
    }

    // TC-REG-04: Đăng ký thất bại - thiếu trường bắt buộc
    public function test_register_fails_when_required_fields_missing(): void
    {
        $response = $this->postJson($this->url, []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false)
                 ->assertJsonStructure(['errors' => ['name', 'email', 'password']]);
    }

    // TC-REG-05: Đăng ký thất bại - email sai định dạng
    public function test_register_fails_with_invalid_email_format(): void
    {
        $response = $this->postJson($this->url, [
            'name'                  => 'Test',
            'email'                 => 'not-an-email',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['email']]);
    }

    // TC-REG-06: Đăng ký thất bại - mật khẩu xác nhận không khớp
    public function test_register_fails_when_password_confirmation_mismatch(): void
    {
        $response = $this->postJson($this->url, [
            'name'                  => 'Test',
            'email'                 => 'x@homi.vn',
            'password'              => '123456',
            'password_confirmation' => 'wrong',
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['password']]);
    }

    // TC-REG-07: Đăng ký thất bại - mật khẩu quá ngắn (< 6 ký tự)
    public function test_register_fails_when_password_too_short(): void
    {
        $response = $this->postJson($this->url, [
            'name'                  => 'Test',
            'email'                 => 'x@homi.vn',
            'password'              => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['password']]);
    }

    // TC-REG-08: Mật khẩu không được trả về trong response
    public function test_password_not_exposed_in_response(): void
    {
        $response = $this->postJson($this->url, [
            'name'                  => 'Test',
            'email'                 => 'safe@homi.vn',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertStatus(201);
        $this->assertArrayNotHasKey('password', $response->json('data.user'));
    }
}
