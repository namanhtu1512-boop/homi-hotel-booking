<?php

namespace Tests\Feature\HotelInfo;

use App\Models\Amenity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test — quản lý thông tin khách sạn singleton (/admin/hotel-info).
 * Không còn create/list/delete/restore/toggle-status vì chỉ có 1 khách sạn duy nhất.
 */
class AdminHotelInfoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_view_hotel_info_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.hotel-info.edit'))
            ->assertOk()
            ->assertViewIs('admin.hotel-info.edit');
    }

    public function test_customer_cannot_view_hotel_info_page(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.hotel-info.edit'))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_hotel_info_page(): void
    {
        $this->get(route('admin.hotel-info.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_update_hotel_info(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.hotel-info.update'), [
                'name'    => 'Homi Updated Hotel',
                'address' => '999 Đường Mới',
            ])
            ->assertRedirect(route('admin.hotel-info.edit'));

        $this->assertDatabaseHas('hotel_info', [
            'id'      => 1,
            'name'    => 'Homi Updated Hotel',
            'address' => '999 Đường Mới',
        ]);
    }

    public function test_update_creates_only_one_row_no_matter_how_many_times(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.hotel-info.update'), [
            'name' => 'Lần 1', 'address' => 'Địa chỉ 1',
        ]);
        $this->actingAs($admin)->put(route('admin.hotel-info.update'), [
            'name' => 'Lần 2', 'address' => 'Địa chỉ 2',
        ]);

        $this->assertDatabaseCount('hotel_info', 1);
        $this->assertDatabaseHas('hotel_info', ['name' => 'Lần 2']);
    }

    public function test_update_fails_when_name_missing(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.hotel-info.update'), ['address' => 'Có địa chỉ'])
            ->assertSessionHasErrors(['name']);
    }

    public function test_update_syncs_amenities(): void
    {
        $amenities = collect([
            Amenity::create(['name' => 'Wifi miễn phí']),
            Amenity::create(['name' => 'Bãi đỗ xe']),
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.hotel-info.update'), [
                'name'        => 'Homi Hotel',
                'address'     => '1 Đường ABC',
                'amenity_ids' => $amenities->pluck('id')->all(),
            ]);

        $this->assertDatabaseHas('hotel_info_amenity', [
            'hotel_id'   => 1,
            'amenity_id' => $amenities->first()->id,
        ]);
    }

    public function test_customer_cannot_update_hotel_info(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->put(route('admin.hotel-info.update'), ['name' => 'Hack', 'address' => 'X'])
            ->assertForbidden();
    }
}
