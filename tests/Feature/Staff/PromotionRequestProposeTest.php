<?php

namespace Tests\Feature\Staff;

use App\Models\Promotion;
use App\Models\PromotionRequest;
use App\Models\User;
use App\Notifications\NewPromotionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PromotionRequestProposeTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsStaff(): User
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff);
        session(['login_context' => 'staff']);

        return $staff;
    }

    public function test_de_xuat_ma_moi_tao_pending_va_bao_admin(): void
    {
        Notification::fake();
        $staff = $this->loginAsStaff();
        $admin = User::factory()->admin()->create();

        $response = $this->post(route('staff.promotion-requests.store'), [
            'code'             => 'KHACHQUEN10',
            'discount_percent' => 10,
            'reason'           => 'Khách quen ở thường xuyên',
        ]);

        $response->assertRedirect(route('staff.group-discount-requests.index'));

        $request = PromotionRequest::where('code', 'KHACHQUEN10')->first();
        $this->assertNotNull($request);
        $this->assertSame('pending', $request->status);
        $this->assertSame($staff->id, $request->user_id);
        $this->assertEquals(10.0, (float) $request->discount_percent);

        Notification::assertSentTo($admin, NewPromotionRequest::class);
    }

    public function test_ma_da_ton_tai_trong_khuyen_mai_bi_chan(): void
    {
        $this->loginAsStaff();
        Promotion::factory()->create(['code' => 'GROUP5']);

        $this->post(route('staff.promotion-requests.store'), [
            'code'             => 'GROUP5',
            'discount_percent' => 10,
        ])->assertSessionHas('error');

        $this->assertSame(0, PromotionRequest::where('code', 'GROUP5')->count());
    }

    public function test_khong_gui_duoc_2_de_xuat_cung_ma_dang_cho_duyet(): void
    {
        $this->loginAsStaff();

        $this->post(route('staff.promotion-requests.store'), [
            'code'             => 'KHACHQUEN10',
            'discount_percent' => 10,
        ]);
        $this->assertSame(1, PromotionRequest::where('code', 'KHACHQUEN10')->count());

        $this->post(route('staff.promotion-requests.store'), [
            'code'             => 'KHACHQUEN10',
            'discount_percent' => 15,
        ])->assertSessionHas('error');

        $this->assertSame(1, PromotionRequest::where('code', 'KHACHQUEN10')->count());
    }
}
