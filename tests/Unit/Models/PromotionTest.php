<?php

namespace Tests\Unit\Models;

use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Promotion::discountFor() — tính tiền giảm giá.
 *
 * Test case ID | Chức năng                                    | Kết quả mong đợi
 * TC-PROMO-001 | Giảm theo phần trăm                           | Đúng % của tổng tiền
 * TC-PROMO-002 | Giảm theo số tiền cố định                     | Đúng số tiền cố định
 * TC-PROMO-003 | discount_percent = 0 nhưng có discount_amount | Dùng discount_amount, không giảm 0đ
 * TC-PROMO-004 | discount_amount lớn hơn tổng tiền              | Giảm tối đa bằng tổng tiền, không âm
 */
class PromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_for_percent(): void
    {
        $promotion = Promotion::factory()->create([
            'discount_percent' => 15,
            'discount_amount'  => null,
        ]);

        $this->assertSame(150000.0, $promotion->discountFor(1000000));
    }

    public function test_discount_for_fixed_amount(): void
    {
        $promotion = Promotion::factory()->create([
            'discount_percent' => null,
            'discount_amount'  => 100000,
        ]);

        $this->assertSame(100000.0, $promotion->discountFor(1000000));
    }

    /**
     * Regression: discount_percent cast 'decimal:2' trả về string "0.00",
     * truthy trong PHP nếu so sánh bằng if() trực tiếp — phải ép kiểu số.
     */
    public function test_discount_for_zero_percent_falls_back_to_fixed_amount(): void
    {
        $promotion = Promotion::factory()->create([
            'discount_percent' => 0,
            'discount_amount'  => 100000,
        ]);

        $this->assertSame(100000.0, $promotion->discountFor(1000000));
    }

    public function test_discount_for_fixed_amount_capped_at_total(): void
    {
        $promotion = Promotion::factory()->create([
            'discount_percent' => null,
            'discount_amount'  => 500000,
        ]);

        $this->assertSame(200000.0, $promotion->discountFor(200000));
    }
}
