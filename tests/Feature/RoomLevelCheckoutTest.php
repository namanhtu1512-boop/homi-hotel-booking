<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Đơn nhiều phòng — mỗi phòng được check-in/check-out ĐỘC LẬP, phí dịch vụ
 * gắn đúng phòng, quyết toán (RoomSettlement) tách riêng từng phòng, đơn chỉ
 * chuyển COMPLETED khi phòng CUỐI CÙNG được trả (xem BookingService::
 * checkIn()/checkOutRoom()).
 */
class RoomLevelCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BookingService
    {
        return app(BookingService::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // login_context='staff' bắt buộc bởi RoleMiddleware (tách phiên
        // admin/staff dù cùng role được phép) — actingAs() không tự set vì
        // nó bỏ qua hẳn LoginController, chỉ cần thiết cho các test đi qua
        // route thật (xem test_luong_http_that_...).
        $this->actingAs(User::factory()->staff()->create());
        $this->withSession(['login_context' => 'staff']);
    }

    private function twoRoomBooking(): array
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 5, 'capacity' => 2, 'price_per_night' => 1000000]);
        $room1 = Room::create(['room_type_id' => $roomType->id, 'room_number' => 'A' . fake()->unique()->numberBetween(100, 999), 'housekeeping_status' => 'clean']);
        $room2 = Room::create(['room_type_id' => $roomType->id, 'room_number' => 'A' . fake()->unique()->numberBetween(100, 999), 'housekeeping_status' => 'clean']);

        $booking = Booking::factory()->create([
            'status'         => BookingStatus::CONFIRMED,
            'check_in'       => now('Asia/Ho_Chi_Minh')->subDay()->toDateString(),
            'check_out'      => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
            'nights'         => 1,
            'total_amount'   => 2000000,
        ]);

        $item = BookingItem::factory()->create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomType->id,
            'quantity'        => 2,
            'nights'          => 1,
            'price_per_night' => 1000000,
            'subtotal'        => 2000000,
            'price_breakdown' => [['nightly_total' => 1000000]],
        ]);

        Payment::create([
            'booking_id'      => $booking->id,
            'method'          => PaymentMethod::PAY_AT_HOTEL,
            'amount'          => 2000000,
            'status'          => PaymentStatus::DEPOSIT_PAID,
            'deposit_amount'  => 600000,
            'deposit_paid_at' => now(),
        ]);

        return [$booking->fresh(['bookingItems', 'payment']), $item, $room1, $room2];
    }

    public function test_checkin_tung_phan_khong_bat_buoc_gan_het_phong_trong_1_luot(): void
    {
        [$booking, $item, $room1, $room2] = $this->twoRoomBooking();

        $result = $this->service()->checkIn($booking, [$item->id => [$room1->id]]);

        $this->assertSame(BookingStatus::CHECKED_IN, $result['booking']->status);
        $this->assertTrue($result['booking']->hasUnassignedRooms());
        $this->assertCount(1, $result['booking']->bookingItemRooms);

        // Check-in nốt phòng còn lại ở lượt sau — không bị lỗi dù đơn đã CHECKED_IN.
        $result2 = $this->service()->checkIn($booking->fresh(), [$item->id => [$room2->id]]);

        $this->assertFalse($result2['booking']->hasUnassignedRooms());
        $this->assertCount(2, $result2['booking']->bookingItemRooms);
    }

    public function test_moi_phong_checkout_va_quyet_toan_doc_lap(): void
    {
        [$booking, $item, $room1, $room2] = $this->twoRoomBooking();

        $this->service()->checkIn($booking, [$item->id => [$room1->id, $room2->id]]);
        $booking = $booking->fresh();

        $bir1 = $booking->bookingItemRooms()->where('room_id', $room1->id)->first();
        $bir2 = $booking->bookingItemRooms()->where('room_id', $room2->id)->first();

        $service = Service::create(['name' => 'Bữa sáng', 'price' => 100000, 'status' => 'active']);
        $this->service()->addServiceItem($booking, $service->id, 1, null, null, $bir1->id);

        // Trả phòng 1 trước — đơn PHẢI vẫn đang lưu trú vì phòng 2 chưa trả.
        $result1 = $this->service()->checkOutRoom($booking, $bir1, ['method' => 'cash']);

        $this->assertFalse($result1['completed']);
        $this->assertSame(BookingStatus::CHECKED_IN, $result1['booking']->status);
        $this->assertSame(1000000.0, (float) $result1['settlement']->room_charge);
        $this->assertSame(100000.0, (float) $result1['settlement']->service_charge);
        $this->assertSame(300000.0, (float) $result1['settlement']->deposit_credit);
        $this->assertSame(800000.0, (float) $result1['settlement']->amount_due);

        // Phòng 2 chưa có dịch vụ riêng — chỉ tiền phòng trừ cọc phân bổ.
        $result2 = $this->service()->checkOutRoom($booking->fresh(), $bir2, ['method' => 'cash']);

        $this->assertTrue($result2['completed']);
        $this->assertSame(BookingStatus::COMPLETED, $result2['booking']->status);
        $this->assertSame(0.0, (float) $result2['settlement']->service_charge);
        $this->assertSame(700000.0, (float) $result2['settlement']->amount_due);

        // Đơn hoàn tất — payment tổng được đồng bộ về PAID để các nơi khác
        // (dashboard, canComplete cũ...) vẫn đọc đúng trạng thái.
        $this->assertSame(PaymentStatus::PAID, $result2['booking']->payment->status);
    }

    /**
     * bookingItem->subtotal luôn là giá TRƯỚC giảm giá (discount_amount trừ
     * riêng ở cấp đơn) — nếu quyết toán từng phòng không trừ lại phần giảm
     * giá chia đều cho phòng đó thì MỌI phòng đều bị cộng khống đúng bằng
     * discount_amount/tổng số phòng vào "còn phải thu", kể cả phòng khách đã
     * thanh toán đủ 100% giá đã giảm (xem computeRoomSettlementAmounts()).
     */
    public function test_giam_gia_duoc_tru_deu_cho_tung_phong_khi_quyet_toan(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 5, 'capacity' => 2, 'price_per_night' => 1000000]);
        $room1 = Room::create(['room_type_id' => $roomType->id, 'room_number' => 'B' . fake()->unique()->numberBetween(100, 999), 'housekeeping_status' => 'clean']);
        $room2 = Room::create(['room_type_id' => $roomType->id, 'room_number' => 'B' . fake()->unique()->numberBetween(100, 999), 'housekeeping_status' => 'clean']);

        $booking = Booking::factory()->create([
            'status'          => BookingStatus::CONFIRMED,
            'check_in'        => now('Asia/Ho_Chi_Minh')->subDay()->toDateString(),
            'check_out'       => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
            'nights'          => 1,
            'total_amount'    => 1800000,
            'discount_amount' => 200000,
        ]);

        $item = BookingItem::factory()->create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomType->id,
            'quantity'        => 2,
            'nights'          => 1,
            'price_per_night' => 1000000,
            'subtotal'        => 2000000,
            'price_breakdown' => [['nightly_total' => 1000000]],
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'method'     => PaymentMethod::PAY_AT_HOTEL,
            'amount'     => 1800000,
            'status'     => PaymentStatus::PAID,
            'paid_at'    => now(),
        ]);

        $booking = $booking->fresh(['bookingItems', 'payment']);
        $this->service()->checkIn($booking, [$item->id => [$room1->id, $room2->id]]);
        $booking = $booking->fresh();

        $bir1 = $booking->bookingItemRooms()->where('room_id', $room1->id)->first();
        $preview = $this->service()->previewRoomSettlement($booking, $bir1);

        // Khách đã thanh toán ĐỦ 1.800.000 (giá đã giảm) — mỗi phòng phải về
        // đúng 0, KHÔNG bị cộng khống 200.000/2 = 100.000 vì thiếu trừ giảm giá.
        $this->assertSame(900000.0, $preview['room_charge']);
        $this->assertSame(900000.0, $preview['deposit_credit']);
        $this->assertSame(0.0, $preview['amount_due']);
    }

    /**
     * Đơn gồm 2 LOẠI phòng khác giá nhau (không phải 2 phòng cùng 1 dòng như
     * các test trên) — bug thật gặp: chia đều tiền đã thanh toán theo ĐẦU
     * PHÒNG khiến phòng rẻ báo dư tiền, phòng đắt báo thiếu tiền dù đơn đã
     * thanh toán đủ. Đồng thời kiểm tra phụ phí phát sinh CHƯA gắn phòng cụ
     * thể (VD phí nhận phòng sớm) được phòng CUỐI CÙNG "gánh" thay vì biến
     * mất khỏi mọi quyết toán (xem computeRoomSettlementAmounts()).
     */
    public function test_quyet_toan_ty_le_dung_khi_2_loai_phong_khac_gia_va_phu_phi_chua_gan_phong(): void
    {
        $typeA = RoomType::factory()->create(['total_rooms' => 5, 'capacity' => 2, 'price_per_night' => 1000000]);
        $typeB = RoomType::factory()->create(['total_rooms' => 5, 'capacity' => 2, 'price_per_night' => 2000000]);
        $roomA = Room::create(['room_type_id' => $typeA->id, 'room_number' => 'C' . fake()->unique()->numberBetween(100, 999), 'housekeeping_status' => 'clean']);
        $roomB = Room::create(['room_type_id' => $typeB->id, 'room_number' => 'C' . fake()->unique()->numberBetween(100, 999), 'housekeeping_status' => 'clean']);

        $booking = Booking::factory()->create([
            'status'       => BookingStatus::CONFIRMED,
            'check_in'     => now('Asia/Ho_Chi_Minh')->subDay()->toDateString(),
            'check_out'    => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
            'nights'       => 1,
            'total_amount' => 3000000,
        ]);

        $itemA = BookingItem::factory()->create([
            'booking_id' => $booking->id, 'room_type_id' => $typeA->id, 'quantity' => 1,
            'nights' => 1, 'price_per_night' => 1000000, 'subtotal' => 1000000,
            'price_breakdown' => [['nightly_total' => 1000000]],
        ]);
        $itemB = BookingItem::factory()->create([
            'booking_id' => $booking->id, 'room_type_id' => $typeB->id, 'quantity' => 1,
            'nights' => 1, 'price_per_night' => 2000000, 'subtotal' => 2000000,
            'price_breakdown' => [['nightly_total' => 2000000]],
        ]);

        Payment::create([
            'booking_id' => $booking->id, 'method' => PaymentMethod::PAY_AT_HOTEL,
            'amount' => 3000000, 'status' => PaymentStatus::PAID, 'paid_at' => now(),
        ]);

        $booking = $booking->fresh(['bookingItems', 'payment']);
        $this->service()->checkIn($booking, [$itemA->id => [$roomA->id], $itemB->id => [$roomB->id]]);
        $booking = $booking->fresh();

        $birA = $booking->bookingItemRooms()->where('room_id', $roomA->id)->first();
        $birB = $booking->bookingItemRooms()->where('room_id', $roomB->id)->first();

        // Phụ phí KHÔNG gắn phòng cụ thể (booking_item_room_id null) — mô
        // phỏng phí nhận phòng sớm/trả phòng muộn tự động không quy được
        // cho 1 phòng.
        app(\App\Services\IncidentalInvoiceService::class)->addItem($booking, 'surcharge', 'Phụ phí chung', 300000);

        // Trả phòng A (rẻ hơn) trước — KHÔNG phải phòng cuối, không gánh phụ
        // phí chung. Tiền phòng đã được thanh toán đúng tỷ lệ nên về đúng 0,
        // KHÔNG âm/dư như công thức chia đều cũ.
        $resultA = $this->service()->checkOutRoom($booking, $birA, ['method' => 'cash']);
        $this->assertSame(1000000.0, (float) $resultA['settlement']->room_charge);
        $this->assertSame(0.0, (float) $resultA['settlement']->service_charge);
        $this->assertSame(1000000.0, (float) $resultA['settlement']->deposit_credit);
        $this->assertSame(0.0, (float) $resultA['settlement']->amount_due);

        // Trả phòng B (đắt hơn, CUỐI CÙNG) — gánh luôn phụ phí chung 300.000đ
        // chưa ai thu, tiền phòng cũng về đúng 0 nhờ chia theo tỷ lệ giá.
        $resultB = $this->service()->checkOutRoom($booking->fresh(), $birB, ['method' => 'cash']);
        $this->assertTrue($resultB['completed']);
        $this->assertSame(2000000.0, (float) $resultB['settlement']->room_charge);
        $this->assertSame(300000.0, (float) $resultB['settlement']->service_charge);
        $this->assertSame(2000000.0, (float) $resultB['settlement']->deposit_credit);
        $this->assertSame(300000.0, (float) $resultB['settlement']->amount_due);
    }

    public function test_khong_the_checkout_2_lan_cung_1_phong(): void
    {
        [$booking, $item, $room1, $room2] = $this->twoRoomBooking();

        $this->service()->checkIn($booking, [$item->id => [$room1->id, $room2->id]]);
        $booking = $booking->fresh();
        $bir1 = $booking->bookingItemRooms()->where('room_id', $room1->id)->first();

        $this->service()->checkOutRoom($booking, $bir1);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service()->checkOutRoom($booking->fresh(), $bir1->fresh());
    }

    /**
     * Đi qua đúng route/controller/view thật (không gọi thẳng service) — bắt
     * lỗi runtime trong Blade (biến undefined, gọi sai quan hệ...) mà unit
     * test gọi thẳng service không phát hiện được.
     */
    public function test_luong_http_that_check_in_tung_phan_them_dich_vu_va_check_out_tung_phong(): void
    {
        [$booking, $item, $room1, $room2] = $this->twoRoomBooking();

        // Trang check-in hiển thị đúng — còn thiếu 2 phòng.
        $this->get(route('staff.bookings.check-in.show', $booking->id))
            ->assertOk()
            ->assertSee('cần chọn', false);

        // Check-in phòng 1 trước.
        $this->post(route('staff.bookings.check-in', $booking->id), [
            'rooms' => [$item->id => [$room1->id]],
        ])->assertRedirect(route('staff.bookings.show', $booking->id));

        $this->assertTrue($booking->fresh()->hasUnassignedRooms());

        // Check-in nốt phòng 2.
        $this->post(route('staff.bookings.check-in', $booking->id), [
            'rooms' => [$item->id => [$room2->id]],
        ])->assertRedirect(route('staff.bookings.show', $booking->id));

        $booking = $booking->fresh();
        $this->assertFalse($booking->hasUnassignedRooms());

        $bir1 = $booking->bookingItemRooms()->where('room_id', $room1->id)->first();
        $service = Service::create(['name' => 'Bữa sáng', 'price' => 100000, 'status' => 'active']);

        $this->post(route('staff.bookings.services.store', $booking->id), [
            'service_id'            => $service->id,
            'quantity'              => 1,
            'booking_item_room_id'  => $bir1->id,
        ])->assertRedirect();

        // Trang check-out liệt kê cả 2 phòng đang chờ trả, kèm số tiền dự kiến.
        $this->get(route('staff.bookings.check-out.show', $booking->id))
            ->assertOk()
            ->assertSee('Phòng ' . $room1->room_number)
            ->assertSee('Phòng ' . $room2->room_number);

        // Trả phòng 1 — chỉ tick phòng 1.
        $this->post(route('staff.bookings.check-out', $booking->id), [
            'rooms'  => [$bir1->id],
            'method' => 'cash',
        ])->assertRedirect(route('staff.bookings.show', $booking->id));

        $booking = $booking->fresh();
        $this->assertSame(BookingStatus::CHECKED_IN, $booking->status);
        $this->assertNotNull($bir1->fresh()->checked_out_at);

        // Hóa đơn RIÊNG phòng 1 (đã trả) — hiển thị đúng, không lỗi view.
        $this->get(route('staff.bookings.invoice', $booking->id) . '?room=' . $bir1->id)
            ->assertOk()
            ->assertSee('ĐÃ THU');

        $bir2 = $booking->bookingItemRooms()->where('room_id', $room2->id)->first();

        // Hóa đơn RIÊNG phòng 2 (chưa trả) — chế độ dự kiến, không lỗi view.
        $this->get(route('staff.bookings.invoice', $booking->id) . '?room=' . $bir2->id)
            ->assertOk()
            ->assertSee('DỰ KIẾN PHẢI THU');

        // Trả nốt phòng 2 — đơn phải hoàn tất.
        $this->post(route('staff.bookings.check-out', $booking->id), [
            'rooms'  => [$bir2->id],
            'method' => 'cash',
        ])->assertRedirect(route('staff.bookings.show', $booking->id));

        $this->assertSame(BookingStatus::COMPLETED, $booking->fresh()->status);

        // Hóa đơn tổng cả đơn (không có ?room=) vẫn hoạt động bình thường.
        $this->get(route('staff.bookings.invoice', $booking->id))->assertOk();
    }
}
