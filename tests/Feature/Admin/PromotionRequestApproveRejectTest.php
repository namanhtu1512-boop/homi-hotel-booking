<?php

namespace Tests\Feature\Admin;

use App\Models\Promotion;
use App\Models\PromotionRequest;
use App\Models\User;
use App\Notifications\PromotionRequestResolved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PromotionRequestApproveRejectTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        session(['login_context' => 'admin']);

        return $admin;
    }

    private function pendingRequestFrom(User $staff, string $code = 'KHACHQUEN10', float $percent = 10): PromotionRequest
    {
        $this->actingAs($staff);
        session(['login_context' => 'staff']);
        $this->post(route('staff.promotion-requests.store'), [
            'code'             => $code,
            'discount_percent' => $percent,
            'reason'           => 'test',
        ]);

        return PromotionRequest::where('code', $code)->latest()->first();
    }

    public function test_duyet_tao_khuyen_mai_that_va_bao_nhan_vien(): void
    {
        Notification::fake();
        $staff = User::factory()->staff()->create();
        $request = $this->pendingRequestFrom($staff);

        $this->loginAsAdmin();
        $this->post(route('admin.promotion-requests.approve', $request->id))
            ->assertRedirect(route('admin.promotions.index'));

        $request->refresh();
        $this->assertSame('approved', $request->status);
        $this->assertNotNull($request->promotion_id);
        $this->assertNotNull($request->handled_by);

        $promotion = Promotion::find($request->promotion_id);
        $this->assertNotNull($promotion);
        $this->assertSame('KHACHQUEN10', $promotion->code);
        $this->assertSame('active', $promotion->status);
        $this->assertTrue((bool) $promotion->is_group_promo);
        $this->assertEquals(10.0, (float) $promotion->discount_percent);

        Notification::assertSentTo($staff, PromotionRequestResolved::class);
    }

    public function test_tu_choi_khong_tao_khuyen_mai(): void
    {
        $staff = User::factory()->staff()->create();
        $request = $this->pendingRequestFrom($staff);

        $this->loginAsAdmin();
        $this->post(route('admin.promotion-requests.reject', $request->id), ['admin_note' => 'Không phù hợp'])
            ->assertRedirect(route('admin.promotions.index'));

        $request->refresh();
        $this->assertSame('rejected', $request->status);
        $this->assertNull($request->promotion_id);
        $this->assertSame('Không phù hợp', $request->admin_note);
        $this->assertSame(0, Promotion::where('code', 'KHACHQUEN10')->count());
    }

    public function test_khong_the_xu_ly_lai_yeu_cau_da_xu_ly(): void
    {
        $staff = User::factory()->staff()->create();
        $request = $this->pendingRequestFrom($staff);

        $this->loginAsAdmin();
        $this->post(route('admin.promotion-requests.approve', $request->id));

        $this->post(route('admin.promotion-requests.approve', $request->id))
            ->assertSessionHas('error');

        $this->assertSame(1, Promotion::where('code', 'KHACHQUEN10')->count());
    }

    public function test_nhan_vien_khong_duyet_duoc(): void
    {
        $staff = User::factory()->staff()->create();
        $request = $this->pendingRequestFrom($staff);

        $this->actingAs($staff);
        session(['login_context' => 'staff']);

        $this->post(route('admin.promotion-requests.approve', $request->id))
            ->assertRedirect(route('staff.dashboard'));

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_ma_bi_trung_luc_duyet_thi_bao_loi(): void
    {
        $staff = User::factory()->staff()->create();
        $request = $this->pendingRequestFrom($staff);

        // Giả lập admin đã tự tạo trùng mã này bằng tay sau khi nhân viên gửi đề xuất.
        Promotion::factory()->create(['code' => 'KHACHQUEN10']);

        $this->loginAsAdmin();
        $this->post(route('admin.promotion-requests.approve', $request->id))
            ->assertSessionHas('error');

        $this->assertSame('pending', $request->fresh()->status);
    }
}
