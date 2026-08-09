<?php

namespace Tests\Feature\Staff;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingItemRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingExtendStaySwitchTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsStaff(): User
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff);
        session(['login_context' => 'staff']);

        return $staff;
    }

    public function test_gia_han_giu_nguyen_phong_khi_loai_phong_con_cho(): void
    {
        $this->loginAsStaff();

        $roomType = RoomType::factory()->create(['total_rooms' => 10, 'capacity' => 2]);
        $room     = Room::create(['room_type_id' => $roomType->id, 'room_number' => 'A101', 'housekeeping_status' => 'clean']);

        $oldCheckIn  = now()->startOfDay()->subDays(2);
        $oldCheckOut = now()->startOfDay();
        $newCheckOut = now()->startOfDay()->addDays(2);

        $booking = Booking::factory()->create([
            'status'    => BookingStatus::CHECKED_IN,
            'check_in'  => $oldCheckIn->toDateString(),
            'check_out' => $oldCheckOut->toDateString(),
            'nights'    => 2,
            'adults'    => 2,
            'children'  => 0,
        ]);

        $item = BookingItem::factory()->create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'quantity'     => 1,
            'adults'       => 2,
            'children'     => 0,
            'nights'       => 2,
        ]);

        BookingItemRoom::create([
            'booking_item_id' => $item->id,
            'room_id'         => $room->id,
            'checked_in_at'   => $oldCheckIn,
        ]);

        $preview = $this->get(route('staff.bookings.extend-stay.preview', $booking->id) . '?new_check_out=' . $newCheckOut->toDateString());
        $preview->assertOk();
        $preview->assertJsonPath('needs_switch', false);

        $response = $this->post(route('staff.bookings.extend-stay.store', $booking->id), [
            'new_check_out' => $newCheckOut->toDateString(),
        ]);
        $response->assertRedirect(route('staff.bookings.show', $booking->id));

        $booking->refresh();
        $this->assertSame($newCheckOut->toDateString(), $booking->check_out->toDateString());
        $this->assertSame(4, $booking->nights);
        $this->assertSame(1, $booking->bookingItems()->count());

        $assignment = BookingItemRoom::where('booking_item_id', $item->id)->first();
        $this->assertSame($room->id, $assignment->room_id);
        $this->assertNull($assignment->checked_out_at);
    }

    public function test_gia_han_de_xuat_doi_loai_phong_khac_khi_loai_hien_tai_het_cho(): void
    {
        $this->loginAsStaff();

        $roomTypeA = RoomType::factory()->create(['total_rooms' => 1, 'capacity' => 2, 'price_per_night' => 500000]);
        $roomA     = Room::create(['room_type_id' => $roomTypeA->id, 'room_number' => 'A101', 'housekeeping_status' => 'clean']);

        $roomTypeB = RoomType::factory()->create(['total_rooms' => 1, 'capacity' => 2, 'price_per_night' => 700000]);
        $roomB     = Room::create(['room_type_id' => $roomTypeB->id, 'room_number' => 'B201', 'housekeeping_status' => 'clean']);

        $oldCheckIn  = now()->startOfDay()->subDays(2);
        $oldCheckOut = now()->startOfDay();
        $newCheckOut = now()->startOfDay()->addDays(2);

        $booking = Booking::factory()->create([
            'status'    => BookingStatus::CHECKED_IN,
            'check_in'  => $oldCheckIn->toDateString(),
            'check_out' => $oldCheckOut->toDateString(),
            'nights'    => 2,
            'adults'    => 2,
            'children'  => 0,
        ]);

        $item = BookingItem::factory()->create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomTypeA->id,
            'quantity'     => 1,
            'adults'       => 2,
            'children'     => 0,
            'nights'       => 2,
        ]);

        BookingItemRoom::create([
            'booking_item_id' => $item->id,
            'room_id'         => $roomA->id,
            'checked_in_at'   => $oldCheckIn,
        ]);

        // Đơn khác giữ nốt chỗ còn lại của loại phòng A đúng khoảng ngày gia hạn
        // (total_rooms=1 nên loại A hết chỗ ngay khi có 1 đơn khác chiếm chỗ).
        $otherBooking = Booking::factory()->create([
            'status'    => BookingStatus::CONFIRMED,
            'check_in'  => $oldCheckOut->toDateString(),
            'check_out' => $newCheckOut->toDateString(),
        ]);
        BookingItem::factory()->create([
            'booking_id'   => $otherBooking->id,
            'room_type_id' => $roomTypeA->id,
            'quantity'     => 1,
            'nights'       => 2,
        ]);

        $preview = $this->get(route('staff.bookings.extend-stay.preview', $booking->id) . '?new_check_out=' . $newCheckOut->toDateString());
        $preview->assertOk();
        $preview->assertJsonPath('needs_switch', true);
        $preview->assertJsonPath('alternatives.0.room_type_id', $roomTypeB->id);
        $preview->assertJsonPath('alternatives.0.available_rooms.0.id', $roomB->id);

        $response = $this->post(route('staff.bookings.extend-stay.store', $booking->id), [
            'new_check_out'       => $newCheckOut->toDateString(),
            'switch_room_type_id' => $roomTypeB->id,
            'switch_room_id'      => $roomB->id,
        ]);
        $response->assertRedirect(route('staff.bookings.show', $booking->id));

        $booking->refresh();
        $this->assertSame($newCheckOut->toDateString(), $booking->check_out->toDateString());
        $this->assertSame(4, $booking->nights);

        $newItem = $booking->bookingItems()->where('room_type_id', $roomTypeB->id)->first();
        $this->assertNotNull($newItem);
        $this->assertSame(2, $newItem->nights);

        $oldAssignment = BookingItemRoom::where('booking_item_id', $item->id)->first();
        $this->assertNotNull($oldAssignment->checked_out_at);

        $roomA->refresh();
        $this->assertSame('dirty', $roomA->housekeeping_status);

        $newAssignment = BookingItemRoom::where('booking_item_id', $newItem->id)->first();
        $this->assertNotNull($newAssignment);
        $this->assertSame($roomB->id, $newAssignment->room_id);
        $this->assertNull($newAssignment->checked_out_at);
    }

    public function test_gia_han_bao_loi_khi_khong_co_phuong_an_thay_the(): void
    {
        $this->loginAsStaff();

        $roomTypeA = RoomType::factory()->create(['total_rooms' => 1, 'capacity' => 2]);
        $roomA     = Room::create(['room_type_id' => $roomTypeA->id, 'room_number' => 'A101', 'housekeeping_status' => 'clean']);

        $oldCheckIn  = now()->startOfDay()->subDays(2);
        $oldCheckOut = now()->startOfDay();
        $newCheckOut = now()->startOfDay()->addDays(2);

        $booking = Booking::factory()->create([
            'status'    => BookingStatus::CHECKED_IN,
            'check_in'  => $oldCheckIn->toDateString(),
            'check_out' => $oldCheckOut->toDateString(),
            'nights'    => 2,
        ]);

        $item = BookingItem::factory()->create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomTypeA->id,
            'quantity'     => 1,
            'nights'       => 2,
        ]);

        BookingItemRoom::create([
            'booking_item_id' => $item->id,
            'room_id'         => $roomA->id,
            'checked_in_at'   => $oldCheckIn,
        ]);

        $otherBooking = Booking::factory()->create([
            'status'    => BookingStatus::CONFIRMED,
            'check_in'  => $oldCheckOut->toDateString(),
            'check_out' => $newCheckOut->toDateString(),
        ]);
        BookingItem::factory()->create([
            'booking_id'   => $otherBooking->id,
            'room_type_id' => $roomTypeA->id,
            'quantity'     => 1,
            'nights'       => 2,
        ]);

        $preview = $this->get(route('staff.bookings.extend-stay.preview', $booking->id) . '?new_check_out=' . $newCheckOut->toDateString());
        $preview->assertStatus(422);
        $preview->assertJsonPath('ok', false);
    }
}
