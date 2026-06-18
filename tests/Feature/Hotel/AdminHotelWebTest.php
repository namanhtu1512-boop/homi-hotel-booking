<?php

namespace Tests\Feature\Hotel;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test — kiểm tra giao diện web quản lý khách sạn (admin/hotels)
 * dùng nút bấm/form thay vì gọi thẳng JSON API.
 */
class AdminHotelWebTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_admin_can_view_hotel_list_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.hotels.index'))
            ->assertOk()
            ->assertViewIs('admin.hotels.index');
    }

    public function test_customer_cannot_view_hotel_list_page(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);

        $this->actingAs($customer)
            ->get(route('admin.hotels.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_hotel_via_form(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.hotels.store'), [
                'name'    => 'Homi Cần Thơ Hotel',
                'city'    => 'Cần Thơ',
                'address' => '10 Hòa Bình, Ninh Kiều',
            ])
            ->assertRedirect(route('admin.hotels.index'));

        $this->assertDatabaseHas('hotels', ['name' => 'Homi Cần Thơ Hotel', 'city' => 'Cần Thơ']);
    }

    public function test_create_form_fails_when_name_missing(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.hotels.store'), [
                'city'    => 'Cần Thơ',
                'address' => '10 Hòa Bình',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_admin_can_update_hotel_via_form(): void
    {
        $hotel = Hotel::factory()->create(['name' => 'Tên Cũ']);

        $this->actingAs($this->admin())
            ->put(route('admin.hotels.update', $hotel->id), [
                'name'    => 'Tên Mới',
                'city'    => $hotel->city,
                'address' => $hotel->address,
            ])
            ->assertRedirect(route('admin.hotels.index'));

        $this->assertDatabaseHas('hotels', ['id' => $hotel->id, 'name' => 'Tên Mới']);
    }

    public function test_admin_can_soft_delete_hotel_via_button(): void
    {
        $hotel = Hotel::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.hotels.destroy', $hotel->id))
            ->assertRedirect(route('admin.hotels.index'));

        $this->assertSoftDeleted('hotels', ['id' => $hotel->id]);
    }

    public function test_admin_can_restore_hotel_via_button(): void
    {
        $hotel = Hotel::factory()->create();
        $hotel->delete();

        $this->actingAs($this->admin())
            ->post(route('admin.hotels.restore', $hotel->id))
            ->assertRedirect(route('admin.hotels.index'));

        $this->assertNotSoftDeleted('hotels', ['id' => $hotel->id]);
    }

    public function test_admin_can_toggle_status_via_button(): void
    {
        $hotel = Hotel::factory()->create(['status' => 'active']);

        $this->actingAs($this->admin())
            ->patch(route('admin.hotels.toggle-status', $hotel->id))
            ->assertRedirect(route('admin.hotels.index'));

        $this->assertDatabaseHas('hotels', ['id' => $hotel->id, 'status' => 'hidden']);
    }

    public function test_admin_can_view_hotel_detail_page(): void
    {
        $hotel = Hotel::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.hotels.show', $hotel->id))
            ->assertOk()
            ->assertViewIs('admin.hotels.show')
            ->assertSee($hotel->name);
    }

    public function test_admin_can_view_create_and_edit_pages(): void
    {
        $hotel = Hotel::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.hotels.create'))
            ->assertOk()
            ->assertViewIs('admin.hotels.form');

        $this->actingAs($admin)
            ->get(route('admin.hotels.edit', $hotel->id))
            ->assertOk()
            ->assertViewIs('admin.hotels.form');
    }
}
