<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * Test API JSON /api/v1/{register,login,me,profile,change-password,logout} —
 * Api\AuthController trước đây hoàn toàn không có test nào, dù đây là 1
 * trong 2 tầng auth của hệ thống (song song với auth Blade session).
 *
 * Test case ID | Chức năng                                | Kết quả mong đợi
 * TC-AAPI-001  | Đăng ký thành công                        | 201, có token, không lộ password
 * TC-AAPI-002  | Đăng ký trùng email                       | 422
 * TC-AAPI-003  | Đăng ký mật khẩu không khớp confirm       | 422
 * TC-AAPI-004  | Đăng nhập đúng, dùng token gọi /me         | 200, đúng user
 * TC-AAPI-005  | Đăng nhập sai mật khẩu                    | 401
 * TC-AAPI-006  | Đăng nhập khi tài khoản bị khóa            | 403
 * TC-AAPI-007  | Gọi /me không kèm token                    | 401
 * TC-AAPI-008  | Cập nhật hồ sơ (name/phone)                | 200, dữ liệu đổi đúng
 * TC-AAPI-009  | Đổi mật khẩu đúng mật khẩu hiện tại         | 200, mật khẩu mới hoạt động
 * TC-AAPI-010  | Đổi mật khẩu sai mật khẩu hiện tại          | 422
 * TC-AAPI-011  | Đăng xuất xóa token, dùng lại token cũ      | Token cũ không còn dùng được
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_returns_token_without_leaking_password(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name'                  => 'Nguyen Van A',
            'email'                 => 'a@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.user.email', 'a@example.com');
        $response->assertJsonMissingPath('data.user.password');
        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('users', ['email' => 'a@example.com', 'role' => 'customer']);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $this->postJson('/api/v1/register', [
            'name'                  => 'Nguyen Van B',
            'email'                 => 'dup@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);
    }

    public function test_register_fails_when_password_confirmation_does_not_match(): void
    {
        $this->postJson('/api/v1/register', [
            'name'                  => 'Nguyen Van C',
            'email'                 => 'c@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'khac-mat-khau',
        ])->assertStatus(422);
    }

    public function test_login_returns_token_usable_to_call_me(): void
    {
        User::factory()->create(['email' => 'login@example.com', 'password' => 'password123']);

        $loginResponse = $this->postJson('/api/v1/login', [
            'email'    => 'login@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertOk();
        $token = $loginResponse->json('data.token');
        $this->assertNotEmpty($token);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'login@example.com');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'login2@example.com', 'password' => 'password123']);

        $this->postJson('/api/v1/login', [
            'email'    => 'login2@example.com',
            'password' => 'sai-mat-khau',
        ])->assertStatus(401);
    }

    public function test_login_fails_when_account_is_locked(): void
    {
        User::factory()->create([
            'email'    => 'locked@example.com',
            'password' => 'password123',
            'status'   => 'locked',
        ]);

        $this->postJson('/api/v1/login', [
            'email'    => 'locked@example.com',
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_update_profile_changes_name_and_phone(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/v1/profile', [
            'name'  => 'Tên Mới',
            'phone' => '0911222333',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Tên Mới');
        $this->assertSame('0911222333', $user->fresh()->phone);
    }

    public function test_change_password_succeeds_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $response = $this->actingAs($user)->putJson('/api/v1/change-password', [
            'current_password'     => 'old-password',
            'password'              => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_change_password_fails_with_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)->putJson('/api/v1/change-password', [
            'current_password'     => 'sai-mat-khau-hien-tai',
            'password'              => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertStatus(422);
    }

    public function test_logout_revokes_current_token(): void
    {
        // Tạo token trực tiếp (không qua Auth::attempt() ở login()) để tránh
        // guard 'web' bị "dính" trạng thái đăng nhập session trong cùng 1
        // test method — nếu gọi qua /api/v1/login trước, request logout kế
        // tiếp có thể được Sanctum xác thực qua session guard thay vì đúng
        // Bearer token, khiến currentAccessToken() trả về TransientToken.
        $user  = User::factory()->create(['email' => 'logout@example.com']);
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/logout')
            ->assertOk();

        $this->assertSame(0, PersonalAccessToken::count());

        // AuthManager cache resolved guard/user trong cùng 1 test method (app
        // container không được dựng lại giữa các lệnh gọi trong cùng method)
        // — phải quên guard đã resolve thì request sau mới thật sự re-xác
        // thực bằng token (giờ đã bị xóa) thay vì dùng lại user đã cache.
        auth()->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }
}
