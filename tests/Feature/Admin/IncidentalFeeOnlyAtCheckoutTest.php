<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingItemRoom;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentalFeeOnlyAtCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        session(['login_context' => 'admin']);

        return $admin;
    }

    private function checkedInBooking(RoomType $roomType, Room $room): Booking
    {
        $booking = Booking::factory()->create([
            'status'    => BookingStatus::CHECKED_IN,
            'check_in'  => now()->startOfDay()->subDay()->toDateString(),
            'check_out' => now()->startOfDay()->addDay()->toDateString(),
            'nights'    => 2,
        ]);

        $item = BookingItem::factory()->create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'quantity'     => 1,
            'nights'       => 2,
        ]);

        BookingItemRoom::create([
            'booking_item_id' => $item->id,
            'room_id'         => $room->id,
            'checked_in_at'   => now()->startOfDay()->subDay(),
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'method'     => PaymentMethod::PAY_AT_HOTEL,
            'amount'     => $booking->total_amount,
            'status'     => PaymentStatus::PAID,
            'paid_at'    => now(),
        ]);

        return $booking;
    }

    public function test_khong_con_nut_them_phu_phi_o_trang_chi_tiet_khi_dang_luu_tru(): void
    {
        $this->loginAsAdmin();

        $roomType = RoomType::factory()->create(['total_rooms' => 5, 'capacity' => 2]);
        $room     = Room::create(['room_type_id' => $roomType->id, 'room_number' => 'A101', 'housekeeping_status' => 'clean']);
        $booking  = $this->checkedInBooking($roomType, $room);

        $response = $this->get(route('admin.bookings.show', $booking->id));

        $response->assertOk();
        $response->assertDontSee('Thêm dịch vụ phát sinh');
        $response->assertDontSee('Thêm phụ phí hỏng/mất đồ');
        $response->assertSee('chỉ được ghi nhận', false);
    }

    public function test_nut_them_phu_phi_hien_thi_o_trang_tra_phong(): void
    {
        $this->loginAsAdmin();

        $roomType = RoomType::factory()->create(['total_rooms' => 5, 'capacity' => 2]);
        $room     = Room::create(['room_type_id' => $roomType->id, 'room_number' => 'A102', 'housekeeping_status' => 'clean']);
        $booking  = $this->checkedInBooking($roomType, $room);

        $response = $this->get(route('admin.bookings.check-out.show', $booking->id));

        $response->assertOk();
        $response->assertSee('Thêm dịch vụ phát sinh');
        $response->assertSee('Thêm phụ phí hỏng/mất đồ');
    }
}
