<?php

namespace Tests\Feature;

use App\Models\GroupDiscountPolicy;
use App\Services\GroupDiscountPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupDiscountPolicyMatchTierTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GroupDiscountPolicyService
    {
        return app(GroupDiscountPolicyService::class);
    }

    public function test_chon_dung_bac_cao_nhat_dat_duoc(): void
    {
        GroupDiscountPolicy::create(['name' => 'Bậc 1', 'min_rooms' => 5, 'discount_percent' => 3, 'status' => 'active']);
        GroupDiscountPolicy::create(['name' => 'Bậc 2', 'min_rooms' => 10, 'discount_percent' => 5, 'status' => 'active']);
        GroupDiscountPolicy::create(['name' => 'Bậc 3', 'min_rooms' => 20, 'discount_percent' => 8, 'status' => 'active']);

        $tier = $this->service()->matchTierFor(15);

        $this->assertNotNull($tier);
        $this->assertSame(10, $tier->min_rooms);
        $this->assertSame('5.00', (string) $tier->discount_percent);
    }

    public function test_khong_dat_bac_nao_thi_tra_ve_null(): void
    {
        GroupDiscountPolicy::create(['min_rooms' => 10, 'discount_percent' => 5, 'status' => 'active']);

        $this->assertNull($this->service()->matchTierFor(3));
    }

    public function test_bo_qua_chinh_sach_inactive_va_da_xoa(): void
    {
        $inactive = GroupDiscountPolicy::create(['min_rooms' => 5, 'discount_percent' => 3, 'status' => 'inactive']);
        $deleted  = GroupDiscountPolicy::create(['min_rooms' => 5, 'discount_percent' => 4, 'status' => 'active']);
        $deleted->delete();

        $this->assertNull($this->service()->matchTierFor(10));
    }
}
