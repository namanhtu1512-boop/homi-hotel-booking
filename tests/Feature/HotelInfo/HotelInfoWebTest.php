<?php

namespace Tests\Feature\HotelInfo;

use App\Models\HotelInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test — giao diện web quản lý thông tin khách sạn (admin/hotel-info)
 * dùng form thay vì gọi thẳng JSON API.
 */
class HotelInfoWebTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_admin_can_view_hotel_info_page(): void
    {
        $this->actingAsAdmin($this->admin())
            ->get(route('admin.hotel-info.show'))
            ->assertOk()
            ->assertViewIs('admin.hotel-info.show');
    }

    public function test_customer_cannot_view_hotel_info_page(): void
    {
        /** @var User $customer */
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);

        $this->actingAs($customer)
            ->get(route('admin.hotel-info.show'))
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_admin_can_view_edit_page(): void
    {
        $this->actingAsAdmin($this->admin())
            ->get(route('admin.hotel-info.edit'))
            ->assertOk()
            ->assertViewIs('admin.hotel-info.edit');
    }

    public function test_admin_can_update_hotel_info_via_form(): void
    {
        HotelInfo::instance();

        $this->actingAsAdmin($this->admin())
            ->put(route('admin.hotel-info.update'), [
                'name'    => 'Homi Cần Thơ Hotel',
                'address' => '10 Hòa Bình, Ninh Kiều',
            ])
            ->assertRedirect(route('admin.hotel-info.show'));

        $this->assertDatabaseHas('hotel_info', ['name' => 'Homi Cần Thơ Hotel']);
    }

    public function test_update_form_fails_when_name_missing(): void
    {
        $this->actingAsAdmin($this->admin())
            ->put(route('admin.hotel-info.update'), [
                'address' => '10 Hòa Bình',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_admin_can_toggle_maintenance_via_button(): void
    {
        HotelInfo::instance()->update(['status' => 'active']);

        $this->actingAsAdmin($this->admin())
            ->patch(route('admin.hotel-info.toggle-maintenance'))
            ->assertRedirect(route('admin.hotel-info.show'));

        $this->assertDatabaseHas('hotel_info', ['status' => 'maintenance']);
    }
}
