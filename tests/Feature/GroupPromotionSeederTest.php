<?php

namespace Tests\Feature;

use App\Models\Promotion;
use Database\Seeders\GroupPromotionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupPromotionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_tao_dung_6_ma_group_promo(): void
    {
        $this->seed(GroupPromotionSeeder::class);

        $codes = ['GROUP5', 'GROUP10', 'GROUP15', 'GROUP20', 'CORPGROUP', 'EARLYGROUP'];

        $this->assertSame(
            6,
            Promotion::whereIn('code', $codes)->where('is_group_promo', true)->count()
        );
    }

    public function test_seeder_gan_dung_gia_tri_theo_bang_yeu_cau(): void
    {
        $this->seed(GroupPromotionSeeder::class);

        $group5 = Promotion::where('code', 'GROUP5')->first();
        $this->assertEquals(5, $group5->discount_percent);
        $this->assertNull($group5->discount_amount);
        $this->assertFalse($group5->stackable);
        $this->assertTrue($group5->is_group_promo);
        $this->assertSame('active', $group5->status);

        $group20 = Promotion::where('code', 'GROUP20')->first();
        $this->assertEquals(20, $group20->discount_percent);

        $corp = Promotion::where('code', 'CORPGROUP')->first();
        $this->assertNull($corp->discount_percent);
        $this->assertSame(500000, $corp->discount_amount);
        $this->assertFalse($corp->stackable);

        $early = Promotion::where('code', 'EARLYGROUP')->first();
        $this->assertEquals(5, $early->discount_percent);
        $this->assertTrue($early->stackable);
    }

    public function test_chay_seeder_2_lan_khong_loi_trung_unique_code(): void
    {
        $this->seed(GroupPromotionSeeder::class);
        $this->seed(GroupPromotionSeeder::class);

        $this->assertSame(6, Promotion::where('is_group_promo', true)->count());
    }
}
