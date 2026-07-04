<?php

namespace Tests\Feature\Wishlist;

use App\Models\RoomType;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Danh sách chờ — khách gom loại phòng muốn đặt trước khi chốt đơn thật.
 * Lưu DB theo tài khoản (wishlist_items), không dùng session.
 */
class WishlistFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_room_type_to_wishlist(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();

        $response = $this->actingAs($customer)
            ->post(route('customer.wishlist.store', $roomType->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('wishlist_items', [
            'user_id'      => $customer->id,
            'room_type_id' => $roomType->id,
            'quantity'     => 1,
        ]);
    }

    public function test_adding_same_room_type_again_accumulates_quantity(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();

        $this->actingAs($customer)->post(route('customer.wishlist.store', $roomType->id), ['quantity' => 2]);
        $this->actingAs($customer)->post(route('customer.wishlist.store', $roomType->id), ['quantity' => 3]);

        $this->assertEquals(1, WishlistItem::where('user_id', $customer->id)->count());
        $this->assertDatabaseHas('wishlist_items', [
            'user_id'      => $customer->id,
            'room_type_id' => $roomType->id,
            'quantity'     => 5,
        ]);
    }

    public function test_accumulated_quantity_is_capped_at_ten(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();

        $this->actingAs($customer)->post(route('customer.wishlist.store', $roomType->id), ['quantity' => 8]);
        $this->actingAs($customer)->post(route('customer.wishlist.store', $roomType->id), ['quantity' => 8]);

        $this->assertDatabaseHas('wishlist_items', [
            'user_id'      => $customer->id,
            'room_type_id' => $roomType->id,
            'quantity'     => 10,
        ]);
    }

    public function test_customer_can_view_own_wishlist(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        WishlistItem::create(['user_id' => $customer->id, 'room_type_id' => $roomType->id, 'quantity' => 2]);

        $this->actingAs($customer)
            ->get(route('customer.wishlist.index'))
            ->assertOk()
            ->assertSee($roomType->name);
    }

    public function test_customer_can_update_wishlist_item_quantity_and_guests(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        $item = WishlistItem::create(['user_id' => $customer->id, 'room_type_id' => $roomType->id, 'quantity' => 1]);

        $this->actingAs($customer)
            ->patch(route('customer.wishlist.update', $item->id), [
                'quantity' => 4,
                'adults'   => 3,
                'children' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('wishlist_items', [
            'id'       => $item->id,
            'quantity' => 4,
            'adults'   => 3,
            'children' => 1,
        ]);
    }

    public function test_customer_can_remove_wishlist_item(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        $item = WishlistItem::create(['user_id' => $customer->id, 'room_type_id' => $roomType->id]);

        $this->actingAs($customer)
            ->delete(route('customer.wishlist.destroy', $item->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('wishlist_items', ['id' => $item->id]);
    }

    public function test_customer_cannot_update_another_customers_wishlist_item(): void
    {
        $owner   = User::factory()->customer()->create();
        $intruder = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        $item = WishlistItem::create(['user_id' => $owner->id, 'room_type_id' => $roomType->id]);

        $this->actingAs($intruder)
            ->patch(route('customer.wishlist.update', $item->id), ['quantity' => 5, 'adults' => 1])
            ->assertNotFound();

        $this->actingAs($intruder)
            ->delete(route('customer.wishlist.destroy', $item->id))
            ->assertNotFound();

        $this->assertDatabaseHas('wishlist_items', ['id' => $item->id, 'quantity' => 1]);
    }

    public function test_staff_and_admin_cannot_use_customer_wishlist_routes(): void
    {
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();
        $roomType = RoomType::factory()->create();

        $this->actingAs($staff)
            ->get(route('customer.wishlist.index'))
            ->assertRedirect(route('staff.dashboard'));

        $this->actingAs($admin)
            ->post(route('customer.wishlist.store', $roomType->id))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_wishlist_page_shows_prefilled_checkout_form_fields(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        WishlistItem::create([
            'user_id' => $customer->id, 'room_type_id' => $roomType->id,
            'quantity' => 2, 'adults' => 3, 'children' => 1,
        ]);

        $response = $this->actingAs($customer)->get(route('customer.wishlist.index'));

        $response->assertOk();
        $response->assertSee('items[0][room_type_id]', false);
        $response->assertSee('value="' . $roomType->id . '"', false);
    }
}
