<?php

namespace Tests\Feature;

use App\Models\GroupBookingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupBookingNumChildrenTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'contact_name' => 'Nguyễn Văn A',
            'email'        => 'khach@test.com',
            'group_size'   => 10,
            'room_count'   => 5,
        ], $overrides);
    }

    public function test_submit_voi_num_children_luu_dung_gia_tri_vao_db(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->post(route('group-bookings.store'), $this->payload([
            'num_children' => 3,
        ]));

        $response->assertRedirect(route('group-bookings.show'));

        $this->assertDatabaseHas('group_booking_requests', [
            'email'        => 'khach@test.com',
            'num_children' => 3,
        ]);
    }

    public function test_submit_khong_nhap_num_children_luu_mac_dinh_khong_loi(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->post(route('group-bookings.store'), $this->payload());

        $response->assertRedirect(route('group-bookings.show'));
        $response->assertSessionDoesntHaveErrors();

        $groupRequest = GroupBookingRequest::where('email', 'khach@test.com')->first();

        $this->assertNotNull($groupRequest);
        $this->assertSame(0, (int) $groupRequest->num_children);
    }

    public function test_submit_num_children_am_bi_loi_validate(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->post(route('group-bookings.store'), $this->payload([
            'num_children' => -1,
        ]));

        $response->assertSessionHasErrors('num_children');
        $this->assertDatabaseMissing('group_booking_requests', ['email' => 'khach@test.com']);
    }

    public function test_trang_chi_tiet_staff_hien_thi_dung_num_children(): void
    {
        $staff = User::factory()->staff()->create();
        $groupRequest = GroupBookingRequest::factory()->create(['num_children' => 4]);

        $response = $this->actingAs($staff)->withSession(['login_context' => 'staff'])
            ->get(route('staff.group-bookings.show', $groupRequest->id));

        $response->assertOk();
        $response->assertSee('Trẻ em (6-11 tuổi)');
        $response->assertSee('4 trẻ');
    }

    public function test_trang_chi_tiet_staff_an_dong_so_tre_em_khi_bang_0(): void
    {
        $staff = User::factory()->staff()->create();
        $groupRequest = GroupBookingRequest::factory()->create(['num_children' => 0]);

        $response = $this->actingAs($staff)->withSession(['login_context' => 'staff'])
            ->get(route('staff.group-bookings.show', $groupRequest->id));

        $response->assertOk();
        $response->assertDontSee('Trẻ em (6-11 tuổi)');
    }

    public function test_trang_chi_tiet_admin_hien_thi_dung_num_children(): void
    {
        $admin = User::factory()->admin()->create();
        $groupRequest = GroupBookingRequest::factory()->create(['num_children' => 2]);

        $response = $this->actingAs($admin)->withSession(['login_context' => 'admin'])
            ->get(route('admin.group-bookings.show', $groupRequest->id));

        $response->assertOk();
        $response->assertSee('Trẻ em (6-11 tuổi)');
        $response->assertSee('2 trẻ');
    }

    public function test_submit_voi_num_infants_luu_dung_gia_tri_vao_db(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->post(route('group-bookings.store'), $this->payload([
            'num_infants' => 2,
        ]));

        $response->assertRedirect(route('group-bookings.show'));

        $this->assertDatabaseHas('group_booking_requests', [
            'email'       => 'khach@test.com',
            'num_infants' => 2,
        ]);
    }

    public function test_submit_khong_nhap_num_infants_luu_mac_dinh_khong_loi(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->post(route('group-bookings.store'), $this->payload());

        $response->assertRedirect(route('group-bookings.show'));
        $response->assertSessionDoesntHaveErrors();

        $groupRequest = GroupBookingRequest::where('email', 'khach@test.com')->first();

        $this->assertNotNull($groupRequest);
        $this->assertSame(0, (int) $groupRequest->num_infants);
    }

    public function test_submit_num_infants_am_bi_loi_validate(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->post(route('group-bookings.store'), $this->payload([
            'num_infants' => -1,
        ]));

        $response->assertSessionHasErrors('num_infants');
        $this->assertDatabaseMissing('group_booking_requests', ['email' => 'khach@test.com']);
    }

    public function test_trang_chi_tiet_staff_hien_thi_dung_num_infants(): void
    {
        $staff = User::factory()->staff()->create();
        $groupRequest = GroupBookingRequest::factory()->create(['num_infants' => 1]);

        $response = $this->actingAs($staff)->withSession(['login_context' => 'staff'])
            ->get(route('staff.group-bookings.show', $groupRequest->id));

        $response->assertOk();
        $response->assertSee('Trẻ sơ sinh (0-5 tuổi)');
        $response->assertSee('1 trẻ');
    }

    public function test_trang_chi_tiet_staff_an_dong_so_tre_so_sinh_khi_bang_0(): void
    {
        $staff = User::factory()->staff()->create();
        $groupRequest = GroupBookingRequest::factory()->create(['num_infants' => 0]);

        $response = $this->actingAs($staff)->withSession(['login_context' => 'staff'])
            ->get(route('staff.group-bookings.show', $groupRequest->id));

        $response->assertOk();
        $response->assertDontSee('Trẻ sơ sinh (0-5 tuổi)');
    }
}
