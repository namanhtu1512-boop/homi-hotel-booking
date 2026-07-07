<?php

namespace Tests\Feature\HotelInfo;

use App\Models\Amenity;
use App\Models\HotelInfo;
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
        $this->actingAsAdmin($this->admin())
            ->get(route('admin.hotel-info.edit'))
            ->assertOk()
            ->assertViewIs('admin.hotel-info.edit');
    }

    public function test_customer_cannot_view_hotel_info_page(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.hotel-info.edit'))
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_guest_cannot_view_hotel_info_page(): void
    {
        $this->get(route('admin.hotel-info.edit'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_update_hotel_info(): void
    {
        $this->actingAsAdmin($this->admin())
            ->put(route('admin.hotel-info.update'), [
                'name'    => 'Homi Updated Hotel',
                'address' => '999 Đường Mới',
            ])
            ->assertRedirect(route('admin.hotel-info.show'));

        $this->assertDatabaseHas('hotel_info', [
            'id'      => 1,
            'name'    => 'Homi Updated Hotel',
            'address' => '999 Đường Mới',
        ]);
    }

    public function test_update_creates_only_one_row_no_matter_how_many_times(): void
    {
        $admin = $this->admin();

        $this->actingAsAdmin($admin)->put(route('admin.hotel-info.update'), [
            'name' => 'Lần 1', 'address' => 'Địa chỉ 1',
        ]);
        $this->actingAsAdmin($admin)->put(route('admin.hotel-info.update'), [
            'name' => 'Lần 2', 'address' => 'Địa chỉ 2',
        ]);

        $this->assertDatabaseCount('hotel_info', 1);
        $this->assertDatabaseHas('hotel_info', ['name' => 'Lần 2']);
    }

    public function test_update_fails_when_name_missing(): void
    {
        $this->actingAsAdmin($this->admin())
            ->put(route('admin.hotel-info.update'), ['address' => 'Có địa chỉ'])
            ->assertSessionHasErrors(['name']);
    }

    public function test_update_syncs_amenities(): void
    {
        $amenities = collect([
            Amenity::create(['name' => 'Wifi miễn phí']),
            Amenity::create(['name' => 'Bãi đỗ xe']),
        ]);

        $this->actingAsAdmin($this->admin())
            ->put(route('admin.hotel-info.update'), [
                'name'        => 'Homi Hotel',
                'address'     => '1 Đường ABC',
                'amenity_ids' => $amenities->pluck('id')->all(),
            ]);

        $this->assertDatabaseHas('hotel_info_amenity', [
            'hotel_info_id' => 1,
            'amenity_id'    => $amenities->first()->id,
        ]);
    }

    public function test_customer_cannot_update_hotel_info(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->put(route('admin.hotel-info.update'), ['name' => 'Hack', 'address' => 'X'])
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_update_with_invalid_star_rating_returns_error(): void
    {
        $this->actingAsAdmin($this->admin())
            ->put(route('admin.hotel-info.update'), [
                'name' => 'Homi Hotel', 'address' => 'X', 'star_rating' => 6,
            ])
            ->assertSessionHasErrors(['star_rating']);
    }

    public function test_update_with_invalid_check_in_time_returns_error(): void
    {
        $this->actingAsAdmin($this->admin())
            ->put(route('admin.hotel-info.update'), [
                'name' => 'Homi Hotel', 'address' => 'X', 'check_in_time' => 'not-a-time',
            ])
            ->assertSessionHasErrors(['check_in_time']);
    }

    public function test_update_replaces_all_images(): void
    {
        $hotel = HotelInfo::instance();
        $hotel->images()->createMany([
            ['path' => 'old/1.jpg', 'sort_order' => 0],
            ['path' => 'old/2.jpg', 'sort_order' => 1],
        ]);

        $this->actingAsAdmin($this->admin())
            ->put(route('admin.hotel-info.update'), [
                'name' => 'Homi Hotel', 'address' => 'X', 'images_text' => 'new/1.jpg',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('hotel_info_images', 1);
        $this->assertDatabaseHas('hotel_info_images', ['path' => 'new/1.jpg']);
        $this->assertDatabaseMissing('hotel_info_images', ['path' => 'old/1.jpg']);
    }

    public function test_room_type_creation_blocked_during_maintenance(): void
    {
        HotelInfo::instance()->update(['status' => 'maintenance']);

        $this->actingAsAdmin($this->admin())
            ->post(route('admin.room-types.store'), [
                'name'            => 'Phòng Test',
                'price_per_night' => 500000,
                'capacity'        => 2,
                'total_rooms'     => 5,
            ])
            ->assertSessionHasErrors(['status']);
    }
}
