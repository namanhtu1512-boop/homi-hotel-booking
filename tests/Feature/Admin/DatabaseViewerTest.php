<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test /admin/database — trang xem nhanh dữ liệu thô. Quan trọng nhất là
 * không được rò rỉ password hash / remember_token ra HTML dưới bất kỳ hình
 * thức nào (cả nội dung hiển thị và title attribute).
 *
 * Test case ID | Chức năng                                  | Kết quả mong đợi
 * TC-DBV-001   | Admin xem trang database                    | 200
 * TC-DBV-002   | Không lộ password hash trong response       | Không thấy chuỗi hash
 * TC-DBV-003   | Không lộ remember_token trong response      | Không thấy giá trị token
 * TC-DBV-004   | Staff/customer không vào được /admin/database | Redirect đúng dashboard
 */
class DatabaseViewerTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    public function test_admin_can_view_database_page(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)->get('/admin/database')->assertOk();
    }

    public function test_password_hash_is_never_exposed(): void
    {
        $admin = $this->makeUser('admin');
        $other = User::factory()->create(['password' => 'a-very-secret-password']);

        $response = $this->actingAsAdmin($admin)->get('/admin/database');

        $response->assertOk();
        // Bcrypt hash luôn bắt đầu bằng $2y$ — không được xuất hiện dưới bất
        // kỳ hình thức nào trong HTML (cell text hay title attribute).
        $response->assertDontSee('$2y$', escape: false);
        $response->assertDontSeeText($other->password);
    }

    public function test_remember_token_is_never_exposed(): void
    {
        $admin = $this->makeUser('admin');
        $other = User::factory()->create(['remember_token' => 'SUPER-SECRET-REMEMBER-TOKEN-VALUE']);

        $response = $this->actingAsAdmin($admin)->get('/admin/database');

        $response->assertOk();
        $response->assertDontSee('SUPER-SECRET-REMEMBER-TOKEN-VALUE');
    }

    public function test_staff_cannot_access_database_viewer(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAsAdmin($staff)
            ->get('/admin/database')
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_customer_cannot_access_database_viewer(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)
            ->get('/admin/database')
            ->assertRedirect(route('customer.dashboard'));
    }
}
