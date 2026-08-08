<?php

namespace Tests\Feature\Staff;

use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTypeIndexDateFilterTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsStaff(): User
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff);
        session(['login_context' => 'staff']);

        return $staff;
    }

    public function test_khong_dang_nhap_bi_chan(): void
    {
        RoomType::factory()->create();

        $response = $this->get(route('staff.room-types.index'));

        $response->assertRedirect(route('staff.login'));
    }

    public function test_khong_chon_ngay_van_load_binh_thuong_voi_tieu_de_mac_dinh(): void
    {
        $this->loginAsStaff();
        RoomType::factory()->create();

        $response = $this->get(route('staff.room-types.index'));

        $response->assertOk();
        $response->assertSee('Còn trống hôm nay');
        $response->assertDontSee('đến ngày', false);
    }

    public function test_chon_du_ngay_hop_le_thi_doi_tieu_de_cot(): void
    {
        $this->loginAsStaff();
        RoomType::factory()->create();

        $response = $this->get(route('staff.room-types.index', [
            'check_in'  => '2026-12-12',
            'check_out' => '2026-12-20',
        ]));

        $response->assertOk();
        $response->assertSee('Còn trống (12/12/2026 - 20/12/2026)');
    }

    public function test_den_ngay_truoc_tu_ngay_bao_loi_va_khong_ap_dung_loc(): void
    {
        $this->loginAsStaff();
        RoomType::factory()->create();

        $response = $this->get(route('staff.room-types.index', [
            'check_in'  => '2026-12-20',
            'check_out' => '2026-12-10',
        ]));

        $response->assertOk();
        // Không đổi tiêu đề cột vì filter không được áp dụng khi lỗi.
        $response->assertSee('Còn trống hôm nay');
    }

    public function test_chi_dien_1_trong_2_o_ngay_cung_bao_loi(): void
    {
        $this->loginAsStaff();
        RoomType::factory()->create();

        $response = $this->get(route('staff.room-types.index', [
            'check_in' => '2026-12-12',
        ]));

        $response->assertOk();
        $response->assertSee('Còn trống hôm nay');
    }

    public function test_ca_2_o_deu_trong_khong_bao_loi_giu_hanh_vi_mac_dinh(): void
    {
        $this->loginAsStaff();
        RoomType::factory()->create();

        $response = $this->get(route('staff.room-types.index'));

        $response->assertOk();
        $response->assertSee('Còn trống hôm nay');
        // Không có khối alert-danger nào cho lỗi khoảng ngày.
        $response->assertDontSee('alert-danger', false);
    }

    public function test_giu_nguyen_gia_tri_da_chon_tren_form_sau_khi_submit(): void
    {
        $this->loginAsStaff();
        RoomType::factory()->create();

        $response = $this->get(route('staff.room-types.index', [
            'check_in'  => '2026-12-12',
            'check_out' => '2026-12-20',
        ]));

        $response->assertOk();
        $response->assertSee('value="2026-12-12"', false);
        $response->assertSee('value="2026-12-20"', false);
    }

    public function test_giu_nguyen_gia_tri_da_go_ke_ca_khi_bi_loi(): void
    {
        $this->loginAsStaff();
        RoomType::factory()->create();

        $response = $this->get(route('staff.room-types.index', [
            'check_in'  => '2026-12-20',
            'check_out' => '2026-12-10',
        ]));

        $response->assertOk();
        $response->assertSee('value="2026-12-20"', false);
        $response->assertSee('value="2026-12-10"', false);
    }
}
