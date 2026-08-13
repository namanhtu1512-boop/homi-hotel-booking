<?php

namespace Tests\Feature\Admin;

use App\Models\GroupDiscountPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupDiscountPolicyCrudTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        session(['login_context' => 'admin']);

        return $admin;
    }

    public function test_admin_tao_sua_xoa_khoi_phuc_chinh_sach(): void
    {
        $this->loginAsAdmin();

        $this->post(route('admin.group-discount-policies.store'), [
            'name'             => 'Đoàn từ 10 phòng',
            'min_rooms'        => 10,
            'discount_percent' => 5,
            'status'           => 'active',
        ])->assertRedirect(route('admin.group-discount-policies.index'));

        $policy = GroupDiscountPolicy::firstWhere('min_rooms', 10);
        $this->assertNotNull($policy);

        $this->put(route('admin.group-discount-policies.update', $policy->id), [
            'name'             => 'Đoàn từ 10 phòng (sửa)',
            'min_rooms'        => 10,
            'discount_percent' => 6,
            'status'           => 'active',
        ])->assertRedirect(route('admin.group-discount-policies.index'));
        $this->assertSame('Đoàn từ 10 phòng (sửa)', $policy->fresh()->name);

        $this->delete(route('admin.group-discount-policies.destroy', $policy->id))
            ->assertRedirect(route('admin.group-discount-policies.index'));
        $this->assertSoftDeleted('group_discount_policies', ['id' => $policy->id]);

        $this->post(route('admin.group-discount-policies.restore', $policy->id))
            ->assertRedirect(route('admin.group-discount-policies.index'));
        $this->assertDatabaseHas('group_discount_policies', ['id' => $policy->id, 'deleted_at' => null]);
    }

    public function test_nhan_vien_khong_truy_cap_duoc(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff);
        session(['login_context' => 'staff']);

        $this->get(route('admin.group-discount-policies.index'))->assertRedirect(route('staff.dashboard'));
    }
}
