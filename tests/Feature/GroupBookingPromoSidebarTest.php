<?php

namespace Tests\Feature;

use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupBookingPromoSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_co_promotion_group_active_thi_hien_dung_trong_sidebar(): void
    {
        Promotion::factory()->groupPromo()->create([
            'code'        => 'GROUPTEST',
            'description' => 'Mo ta test group promo',
        ]);

        $response = $this->get(route('group-bookings.show'));

        $response->assertOk();
        $response->assertSee('Mã giảm giá đoàn/nhóm hiện có:');
        $response->assertSee('GROUPTEST');
        $response->assertSee('Mo ta test group promo');
    }

    public function test_promotion_khong_phai_group_thi_khong_hien_muc_nay(): void
    {
        Promotion::factory()->create(['code' => 'NORMALPROMO', 'is_group_promo' => false]);

        $response = $this->get(route('group-bookings.show'));

        $response->assertOk();
        $response->assertDontSee('Mã giảm giá đoàn/nhóm hiện có:');
        $response->assertDontSee('NORMALPROMO');
    }

    public function test_khong_co_promotion_nao_thi_an_muc_nay(): void
    {
        $response = $this->get(route('group-bookings.show'));

        $response->assertOk();
        $response->assertDontSee('Mã giảm giá đoàn/nhóm hiện có:');
    }

    public function test_promotion_group_da_het_han_khong_hien_du_is_group_promo_true(): void
    {
        Promotion::factory()->groupPromo()->expired()->create(['code' => 'EXPIREDGROUP']);

        $response = $this->get(route('group-bookings.show'));

        $response->assertOk();
        $response->assertDontSee('EXPIREDGROUP');
        $response->assertDontSee('Mã giảm giá đoàn/nhóm hiện có:');
    }

    public function test_promotion_group_chua_toi_ngay_bat_dau_khong_hien(): void
    {
        Promotion::factory()->groupPromo()->create([
            'code'      => 'FUTUREGROUP',
            'starts_at' => now()->addMonth(),
            'ends_at'   => now()->addMonths(2),
        ]);

        $response = $this->get(route('group-bookings.show'));

        $response->assertOk();
        $response->assertDontSee('FUTUREGROUP');
    }

    public function test_promotion_group_status_hidden_khong_hien(): void
    {
        Promotion::factory()->groupPromo()->create([
            'code'   => 'HIDDENGROUP',
            'status' => 'hidden',
        ]);

        $response = $this->get(route('group-bookings.show'));

        $response->assertOk();
        $response->assertDontSee('HIDDENGROUP');
    }
}
