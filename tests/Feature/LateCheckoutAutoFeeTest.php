<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingItemRoom;
use App\Models\HotelInfo;
use App\Models\LateCheckoutRequest;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Trả phòng muộn KHÔNG xin phép trước — trước đây hệ thống không tự tính
 * phí gì cả (chỉ dựa vào lễ tân nhớ cộng thủ công), giờ có lớp phí tự động
 * dự phòng (BookingService::applyLateCheckoutSurchargeIfNeeded()), đối xứng
 * với phí nhận phòng sớm tự động.
 */
class LateCheckoutAutoFeeTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BookingService
    {
        return app(BookingService::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->staff()->create());
    }

    private function checkedInBookingCheckingOutToday(): Booking
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 5, 'capacity' => 2, 'price_per_night' => 1000000]);
        $room     = Room::create(['room_type_id' => $roomType->id, 'room_number' => 'A' . fake()->unique()->numberBetween(100, 999), 'housekeeping_status' => 'clean']);

        $booking = Booking::factory()->create([
            'status'    => BookingStatus::CHECKED_IN,
            'check_in'  => now('Asia/Ho_Chi_Minh')->subDay()->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->toDateString(),
            'nights'    => 1,
        ]);

        $item = BookingItem::factory()->create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomType->id,
            'quantity'        => 1,
            'nights'          => 1,
            'price_per_night' => 1000000,
            'subtotal'        => 1000000,
            'price_breakdown' => [['nightly_total' => 1000000]],
        ]);

        BookingItemRoom::create([
            'booking_item_id' => $item->id,
            'room_id'         => $room->id,
            'checked_in_at'   => now()->subDay(),
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'method'     => PaymentMethod::PAY_AT_HOTEL,
            'amount'     => $booking->total_amount,
            'status'     => PaymentStatus::PAID,
            'paid_at'    => now(),
        ]);

        return $booking->fresh(['bookingItems']);
    }

    public function test_tra_phong_muon_khong_xin_phep_bi_tu_dong_tinh_phi(): void
    {
        HotelInfo::instance()->update(['check_out_time' => '12:00:00']);

        $this->travelTo(now('Asia/Ho_Chi_Minh')->setTime(13, 30));

        $booking = $this->checkedInBookingCheckingOutToday();

        $room = $booking->bookingItemRooms()->first();
        $result = $this->service()->checkOutRoom($booking, $room);

        // 13:30 - giờ chuẩn 12:00 = trễ 1.5h → rơi vào bậc "đến 2 giờ" = 30% giá phòng đêm cuối (1.000.000đ) = 300.000đ.
        $this->assertSame(300000.0, $result['late_checkout_fee']);
        $this->assertSame(BookingStatus::COMPLETED, $result['booking']->status);
        $this->assertSame(300000.0, (float) $booking->fresh()->incidentalInvoice->total_amount);
    }

    public function test_tra_phong_dung_gio_khong_bi_tinh_phi(): void
    {
        HotelInfo::instance()->update(['check_out_time' => '12:00:00']);

        $this->travelTo(now('Asia/Ho_Chi_Minh')->setTime(11, 30));

        $booking = $this->checkedInBookingCheckingOutToday();

        $room = $booking->bookingItemRooms()->first();
        $result = $this->service()->checkOutRoom($booking, $room);

        $this->assertNull($result['late_checkout_fee']);
        $this->assertNull($booking->fresh()->incidentalInvoice);
    }

    public function test_da_co_yeu_cau_duoc_duyet_thi_khong_tinh_phi_2_lan(): void
    {
        HotelInfo::instance()->update(['check_out_time' => '12:00:00']);

        $this->travelTo(now('Asia/Ho_Chi_Minh')->setTime(13, 30));

        $booking = $this->checkedInBookingCheckingOutToday();

        LateCheckoutRequest::create([
            'booking_id'              => $booking->id,
            'user_id'                 => $booking->user_id ?? User::factory()->create()->id,
            'requested_checkout_time' => '14:00',
            'hours_late'              => 2,
            'fee_amount'              => 300000,
            'status'                  => 'approved',
            'handled_at'              => now(),
        ]);

        $room = $booking->bookingItemRooms()->first();
        $result = $this->service()->checkOutRoom($booking, $room);

        $this->assertNull($result['late_checkout_fee']);
    }
}
