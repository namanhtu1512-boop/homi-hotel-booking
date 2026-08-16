<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use App\Models\User;
use App\Services\RoomChangeRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Đổi phòng cho đơn ĐẶT ĐOÀN (nhiều loại phòng/BookingItem) — trước đây
 * RoomChangeRequestService từ chối hoàn toàn nếu đơn có > 1 loại phòng, giờ
 * cho phép đổi 1 dòng cụ thể (không cho tự đổi ngày ở vì ảnh hưởng cả đoàn).
 */
class RoomChangeRequestGroupBookingTest extends TestCase
{
    use RefreshDatabase;

    private function service(): RoomChangeRequestService
    {
        return app(RoomChangeRequestService::class);
    }

    private function groupBooking(): array
    {
        $checkIn  = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(12)->toDateString();

        $customer = User::factory()->create();

        $booking = Booking::factory()->create([
            'user_id'         => $customer->id,
            'status'          => BookingStatus::CONFIRMED,
            'check_in'        => $checkIn,
            'check_out'       => $checkOut,
            'nights'          => 2,
            'total_amount'    => 3000000,
            'discount_amount' => 0,
        ]);

        $roomTypeA = RoomType::factory()->create(['total_rooms' => 5, 'capacity' => 2, 'price_per_night' => 500000]);
        $roomTypeB = RoomType::factory()->create(['total_rooms' => 5, 'capacity' => 2, 'price_per_night' => 1000000]);
        $roomTypeC = RoomType::factory()->create(['total_rooms' => 5, 'capacity' => 2, 'price_per_night' => 800000]);

        $itemA = BookingItem::factory()->create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomTypeA->id,
            'quantity'        => 1,
            'adults'          => 2,
            'children'        => 0,
            'price_per_night' => 500000,
            'nights'          => 2,
            'subtotal'        => 1000000,
            'child_surcharge' => 0,
        ]);

        $itemB = BookingItem::factory()->create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomTypeB->id,
            'quantity'        => 1,
            'adults'          => 2,
            'children'        => 0,
            'price_per_night' => 1000000,
            'nights'          => 2,
            'subtotal'        => 2000000,
            'child_surcharge' => 0,
        ]);

        return compact('booking', 'customer', 'itemA', 'itemB', 'roomTypeA', 'roomTypeB', 'roomTypeC');
    }

    public function test_dat_doan_bat_buoc_chon_dong_muon_doi(): void
    {
        ['booking' => $booking, 'customer' => $customer, 'roomTypeC' => $roomTypeC] = $this->groupBooking();

        $this->expectException(ValidationException::class);

        $this->service()->create($booking, $customer, [
            'requested_room_type_id' => $roomTypeC->id,
        ]);
    }

    public function test_dat_doan_khong_cho_tu_doi_ngay(): void
    {
        ['booking' => $booking, 'customer' => $customer, 'itemA' => $itemA] = $this->groupBooking();

        $this->expectException(ValidationException::class);

        $this->service()->create($booking, $customer, [
            'booking_item_id'     => $itemA->id,
            'requested_check_in'  => now()->addDays(20)->toDateString(),
            'requested_check_out' => now()->addDays(22)->toDateString(),
        ]);
    }

    public function test_dat_doan_doi_dung_1_dong_khong_anh_huong_dong_khac(): void
    {
        ['booking' => $booking, 'customer' => $customer, 'itemA' => $itemA, 'itemB' => $itemB, 'roomTypeC' => $roomTypeC] = $this->groupBooking();
        $staff = User::factory()->staff()->create();

        $request = $this->service()->create($booking, $customer, [
            'booking_item_id'         => $itemA->id,
            'requested_room_type_id'  => $roomTypeC->id,
        ]);

        $this->assertSame($itemA->id, $request->booking_item_id);
        $this->assertSame($itemA->room_type_id, $request->current_room_type_id);

        $result = $this->service()->approve($request, $staff);

        $itemA->refresh();
        $itemB->refresh();
        $booking->refresh();

        $this->assertSame($roomTypeC->id, $itemA->room_type_id);
        $this->assertSame(2000000.0, (float) $itemB->subtotal, 'Dòng B (không đổi) phải giữ nguyên.');

        // total mới = tổng cũ (3.000.000) - subtotal cũ của A (1.000.000) + subtotal mới của A (800.000*2=1.600.000)
        $this->assertSame(3600000.0, (float) $booking->total_amount);
        $this->assertSame('approved', $result['request']->status);
    }
}
