<?php

namespace Tests\Feature\Staff;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Khu vực /staff/* — tách biệt hoàn toàn với /admin/*: không có quản lý
 * người dùng, xóa loại phòng, sửa thông tin khách sạn, hay xem database thô.
 * Trước bản audit này khu vực Staff hoàn toàn chưa có test tự động nào.
 */
class StaffAreaAccessTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->staff()->create();
    }

    public function test_staff_can_view_dashboard(): void
    {
        $this->actingAsAdmin($this->staff())
            ->get(route('staff.dashboard'))
            ->assertOk();
    }

    public function test_staff_can_view_and_manage_room_types(): void
    {
        $staff = $this->staff();

        $this->actingAsAdmin($staff)
            ->get(route('staff.room-types.index'))
            ->assertOk();

        $this->actingAsAdmin($staff)
            ->post(route('staff.room-types.store'), [
                'name'            => 'Phòng Staff Tạo',
                'price_per_night' => 700000,
                'capacity'        => 2,
                'total_rooms'     => 5,
            ])
            ->assertRedirect(route('staff.room-types.index'));

        $this->assertDatabaseHas('room_types', ['name' => 'Phòng Staff Tạo']);

        $roomType = RoomType::where('name', 'Phòng Staff Tạo')->firstOrFail();

        $this->actingAsAdmin($staff)
            ->put(route('staff.room-types.update', $roomType->id), [
                'name'            => 'Phòng Staff Sửa',
                'price_per_night' => 750000,
                'capacity'        => 2,
                'total_rooms'     => 5,
            ])
            ->assertRedirect(route('staff.room-types.index'));

        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'name' => 'Phòng Staff Sửa']);
    }

    public function test_staff_can_view_hotel_info_but_route_has_no_edit_action(): void
    {
        $this->actingAsAdmin($this->staff())
            ->get(route('staff.hotel-info.show'))
            ->assertOk();

        $this->assertFalse(\Illuminate\Support\Facades\Route::has('staff.hotel-info.update'));
    }

    public function test_staff_can_confirm_booking_and_update_payment(): void
    {
        $staff    = $this->staff();
        $roomType = RoomType::factory()->create();

        $booking = Booking::create([
            'booking_code'   => 'TEST-' . uniqid(),
            'check_in'       => now()->addDays(3),
            'check_out'      => now()->addDays(5),
            'nights'         => 2,
            'customer_name'  => 'Khách Test',
            'customer_phone' => '0900000000',
            'total_amount'   => 1000000,
            'status'         => 'pending',
        ]);

        BookingItem::create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomType->id,
            'quantity'        => 1,
            'price_per_night' => 500000,
            'nights'          => 2,
            'subtotal'        => 1000000,
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'method'     => 'pay_at_hotel',
            'amount'     => 1000000,
            'status'     => 'unpaid',
        ]);

        $this->actingAsAdmin($staff)
            ->post(route('staff.bookings.confirm', $booking->id))
            ->assertRedirect(route('staff.bookings.show', $booking->id));

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);

        $this->actingAsAdmin($staff)
            ->patch(route('staff.bookings.update-payment', $booking->id), ['status' => 'paid'])
            ->assertRedirect(route('staff.bookings.show', $booking->id));
    }

    public function test_staff_can_view_payments_list(): void
    {
        $this->actingAsAdmin($this->staff())
            ->get(route('staff.payments.index'))
            ->assertOk();
    }

    public function test_admin_cannot_access_staff_area(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAsAdmin($admin)
            ->get(route('staff.dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_customer_cannot_access_staff_area(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('staff.dashboard'))
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_staff_cannot_access_admin_only_user_management(): void
    {
        $this->actingAsAdmin($this->staff())
            ->get(route('admin.users.index'))
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_staff_cannot_delete_room_types(): void
    {
        $roomType = RoomType::factory()->create();

        $this->actingAsAdmin($this->staff())
            ->delete(route('admin.room-types.destroy', $roomType->id))
            ->assertRedirect(route('staff.dashboard'));

        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'deleted_at' => null]);
    }

    public function test_staff_cannot_edit_hotel_info(): void
    {
        $this->actingAsAdmin($this->staff())
            ->put(route('admin.hotel-info.update'), ['name' => 'Hack', 'address' => 'X'])
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_staff_cannot_view_raw_database_page(): void
    {
        $this->actingAsAdmin($this->staff())
            ->get(route('admin.database'))
            ->assertRedirect(route('staff.dashboard'));
    }
}
