<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $status = 'active'): User
    {
        return User::create([
            'name'     => ucfirst($role) . ' User',
            'email'    => $role . '@homi.vn',
            'password' => Hash::make('123456'),
            'role'     => $role,
            'status'   => $status,
        ]);
    }

    // TC-RBAC-01: Customer lấy được thông tin của chính mình
    public function test_customer_can_access_own_profile(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)
             ->getJson('/api/v1/me')
             ->assertStatus(200)
             ->assertJsonPath('data.user.role', 'customer');
    }

    // TC-RBAC-02: Staff lấy được thông tin của chính mình
    public function test_staff_can_access_own_profile(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)
             ->getJson('/api/v1/me')
             ->assertStatus(200)
             ->assertJsonPath('data.user.role', 'staff');
    }

    // TC-RBAC-03: Admin lấy được thông tin của chính mình
    public function test_admin_can_access_own_profile(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
             ->getJson('/api/v1/me')
             ->assertStatus(200)
             ->assertJsonPath('data.user.role', 'admin');
    }

    // TC-RBAC-04: Tài khoản bị khóa không đăng nhập được
    public function test_inactive_account_cannot_login(): void
    {
        $this->makeUser('customer', 'locked');

        $this->postJson('/api/v1/login', [
            'email'    => 'customer@homi.vn',
            'password' => '123456',
        ])->assertStatus(403);
    }

    // TC-RBAC-05: Request không có token bị từ chối với 401
    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
        $this->putJson('/api/v1/profile', [])->assertStatus(401);
        $this->putJson('/api/v1/change-password', [])->assertStatus(401);
        $this->postJson('/api/v1/logout')->assertStatus(401);
    }

    // TC-RBAC-06: Mỗi role đăng ký đều nhận role mặc định là 'customer'
    public function test_newly_registered_user_has_customer_role(): void
    {
        $this->postJson('/api/v1/register', [
            'name'                  => 'New User',
            'email'                 => 'newuser@homi.vn',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ])->assertStatus(201)
          ->assertJsonPath('data.user.role', 'customer');
    }

    // TC-RBAC-07: Token hết hạn / đã thu hồi trả về 401
    public function test_revoked_token_returns_401(): void
    {
        $user = $this->makeUser('customer');

        // Tạo token rồi thu hồi ngay
        $token = $user->createToken('test')->plainTextToken;
        $user->tokens()->delete();

        $this->withToken($token)
             ->getJson('/api/v1/me')
             ->assertStatus(401);
    }
}
