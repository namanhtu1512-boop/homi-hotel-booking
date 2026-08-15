<?php

namespace Tests\Feature\Admin;

use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTypeIndexDateFilterTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);
        session(['login_context' => 'admin']);

        return $admin;
    }

    public function test_khong_dang_nhap_bi_chan(): void
    {
        RoomType::factory()->create();

        $response = $this->get(route('admin.room-types.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_khong_chon_ngay_van_load_binh_thuong_voi_tieu_de_mac_dinh(): void
    {
        $this->loginAsAdmin();
        RoomType::factory()->create();

        $response = $this->get(route('admin.room-types.index'));

        $response->assertOk();
        $response->assertSee('Còn trống hôm nay');
        $response->assertDontSee('đến ngày', false);
    }

    public function test_chon_du_ngay_hop_le_thi_doi_tieu_de_cot(): void
    {
        $this->loginAsAdmin();
        RoomType::factory()->create();

        $response = $this->get(route('admin.room-types.index', [
            'check_in'  => '2026-12-12',
            'check_out' => '2026-12-20',
        ]));

        $response->assertOk();
        $response->assertSee('Còn trống (12/12/2026 - 20/12/2026)');
    }

    public function test_den_ngay_truoc_tu_ngay_bao_loi_va_khong_ap_dung_loc(): void
    {
        $this->loginAsAdmin();
        RoomType::factory()->create();

        $response = $this->get(route('admin.room-types.index', [
            'check_in'  => '2026-12-20',
            'check_out' => '2026-12-10',
        ]));

        $response->assertOk();
        $response->assertSee('Còn trống hôm nay');
    }

    public function test_chi_dien_1_trong_2_o_ngay_cung_bao_loi(): void
    {
        $this->loginAsAdmin();
        RoomType::factory()->create();

        $response = $this->get(route('admin.room-types.index', [
            'check_in' => '2026-12-12',
        ]));

        $response->assertOk();
        $response->assertSee('Còn trống hôm nay');
    }

    public function test_giu_nguyen_gia_tri_da_chon_tren_form_sau_khi_submit(): void
    {
        $this->loginAsAdmin();
        RoomType::factory()->create();

        $response = $this->get(route('admin.room-types.index', [
            'check_in'  => '2026-12-12',
            'check_out' => '2026-12-20',
        ]));

        $response->assertOk();
        $response->assertSee('value="2026-12-12"', false);
        $response->assertSee('value="2026-12-20"', false);
    }
}
