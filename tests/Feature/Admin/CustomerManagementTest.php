<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE1 - Tuần 13/14 (US09): /admin/customers tách khỏi /admin/users — tìm
 * kiếm, khóa/mở, xem lịch sử đặt phòng của từng khách hàng.
 */
class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_view_customer_list(): void
    {
        User::factory()->customer()->create(['name' => 'Nguyễn Văn A']);
        User::factory()->staff()->create(['name' => 'Nhân viên B']);

        $response = $this->actingAsAdmin($this->admin())
            ->get(route('admin.customers.index'));

        $response->assertOk();
        $response->assertSee('Nguyễn Văn A');
        $response->assertDontSee('Nhân viên B');
    }

    public function test_admin_can_search_customers_by_name_or_email(): void
    {
        User::factory()->customer()->create(['name' => 'Trần Thị B', 'email' => 'tranthib@example.com']);
        User::factory()->customer()->create(['name' => 'Lê Văn C', 'email' => 'levanc@example.com']);

        $response = $this->actingAsAdmin($this->admin())
            ->get(route('admin.customers.index', ['search' => 'Trần']));

        $response->assertSee('Trần Thị B');
        $response->assertDontSee('Lê Văn C');
    }

    public function test_admin_can_view_customer_detail_with_booking_history(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Phạm Văn D']);
        $roomType = RoomType::factory()->create();

        $booking = Booking::create([
            'user_id'        => $customer->id,
            'booking_code'   => 'HOMI-TEST-001',
            'check_in'       => now()->addDays(3),
            'check_out'      => now()->addDays(5),
            'nights'         => 2,
            'customer_name'  => $customer->name,
            'customer_phone' => '0900000000',
            'total_amount'   => 1000000,
            'status'         => 'confirmed',
        ]);

        BookingItem::create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomType->id,
            'quantity'        => 1,
            'price_per_night' => 500000,
            'nights'          => 2,
            'subtotal'        => 1000000,
        ]);

        $response = $this->actingAsAdmin($this->admin())
            ->get(route('admin.customers.show', $customer->id));

        $response->assertOk();
        $response->assertSee('Phạm Văn D');
        $response->assertSee('HOMI-TEST-001');
    }

    public function test_admin_can_toggle_customer_status(): void
    {
        $customer = User::factory()->customer()->create(['status' => 'active']);

        $this->actingAsAdmin($this->admin())
            ->patch(route('admin.customers.toggle-status', $customer->id))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $customer->id, 'status' => 'locked']);
    }

    public function test_staff_cannot_access_customer_management(): void
    {
        $staff    = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($staff)
            ->get(route('admin.customers.index'))
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_customer_cannot_access_customer_management(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.customers.index'))
            ->assertRedirect(route('customer.dashboard'));
    }
}
