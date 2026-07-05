<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test màn /admin/customers — tách biệt với /admin/users (US09):
 * tìm kiếm, lọc, xem lịch sử đặt phòng của khách hàng.
 *
 * Test case ID | Chức năng                                    | Kết quả mong đợi
 * TC-ADC-001   | Admin xem danh sách khách hàng                | 200
 * TC-ADC-002   | Staff không xem được danh sách khách hàng     | redirect staff.dashboard
 * TC-ADC-003   | Customer không xem được danh sách khách hàng  | redirect customer.dashboard
 * TC-ADC-004   | Guest xem danh sách khách hàng                 | redirect admin.login
 * TC-ADC-005   | Tìm kiếm theo tên/email                        | Chỉ trả khách khớp
 * TC-ADC-006   | Lọc theo trạng thái khóa                       | Chỉ trả khách đúng trạng thái
 * TC-ADC-007   | Admin xem chi tiết + lịch sử đặt phòng         | Thấy đúng đơn của khách đó
 * TC-ADC-008   | Không hiển thị đơn của khách khác trong lịch sử | Không thấy booking_code khách khác
 * TC-ADC-009   | Danh sách không lẫn admin/staff                 | Không thấy tài khoản admin/staff
 */
class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    public function test_admin_can_view_customers_list(): void
    {
        $admin = $this->makeUser('admin');
        User::factory()->customer()->create();

        $this->actingAsAdmin($admin)->get('/admin/customers')->assertOk();
    }

    public function test_staff_cannot_view_customers_list(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAsAdmin($staff)
            ->get('/admin/customers')
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_customer_cannot_view_customers_list(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)
            ->get('/admin/customers')
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_guest_is_redirected_to_admin_login_for_customers_list(): void
    {
        $this->get('/admin/customers')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_search_customers_by_name(): void
    {
        $admin = $this->makeUser('admin');
        $match = User::factory()->customer()->create(['name' => 'Nguyen Van Search']);
        User::factory()->customer()->create(['name' => 'Someone Else']);

        $response = $this->actingAsAdmin($admin)->get('/admin/customers?search=Search');

        $response->assertOk();
        $response->assertSee($match->email);
    }

    public function test_admin_can_filter_customers_by_locked_status(): void
    {
        $admin  = $this->makeUser('admin');
        $locked = User::factory()->customer()->locked()->create();
        $active = User::factory()->customer()->create();

        $response = $this->actingAsAdmin($admin)->get('/admin/customers?status=locked');

        $response->assertOk();
        $response->assertSee($locked->email);
        $response->assertDontSee($active->email);
    }

    public function test_customers_list_does_not_include_admin_or_staff(): void
    {
        $admin        = $this->makeUser('admin');
        $staffAccount = User::factory()->staff()->create(['name' => 'Staff Nguyen']);

        $response = $this->actingAsAdmin($admin)->get('/admin/customers');

        $response->assertOk();
        $response->assertDontSee($staffAccount->email);
    }

    public function test_admin_can_view_customer_booking_history(): void
    {
        $admin    = $this->makeUser('admin');
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);

        $booking = Booking::create([
            'booking_code'   => 'HIST-' . uniqid(),
            'user_id'        => $customer->id,
            'check_in'       => now()->addDays(5)->format('Y-m-d'),
            'check_out'      => now()->addDays(7)->format('Y-m-d'),
            'nights'         => 2,
            'customer_name'  => $customer->name,
            'customer_phone' => '0900000000',
            'total_amount'   => 2000000,
            'status'         => 'confirmed',
        ]);

        BookingItem::create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomType->id,
            'quantity'        => 1,
            'price_per_night' => $roomType->price_per_night,
            'nights'          => 2,
            'subtotal'        => 2000000,
        ]);

        $response = $this->actingAsAdmin($admin)->get("/admin/customers/{$customer->id}");

        $response->assertOk();
        $response->assertSee($booking->booking_code);
    }

    public function test_customer_booking_history_does_not_show_other_customers_bookings(): void
    {
        $admin     = $this->makeUser('admin');
        $customerA = User::factory()->customer()->create();
        $customerB = User::factory()->customer()->create();

        $otherBooking = Booking::create([
            'booking_code'   => 'OTHER-' . uniqid(),
            'user_id'        => $customerB->id,
            'check_in'       => now()->addDays(5)->format('Y-m-d'),
            'check_out'      => now()->addDays(7)->format('Y-m-d'),
            'nights'         => 2,
            'customer_name'  => $customerB->name,
            'customer_phone' => '0900000000',
            'total_amount'   => 2000000,
            'status'         => 'confirmed',
        ]);

        $response = $this->actingAsAdmin($admin)->get("/admin/customers/{$customerA->id}");

        $response->assertOk();
        $response->assertDontSee($otherBooking->booking_code);
    }
}
