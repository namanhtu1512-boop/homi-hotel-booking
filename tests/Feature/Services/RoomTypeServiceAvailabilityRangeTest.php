<?php

namespace Tests\Feature\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use App\Services\RoomTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTypeServiceAvailabilityRangeTest extends TestCase
{
    use RefreshDatabase;

    private function service(): RoomTypeService
    {
        return app(RoomTypeService::class);
    }

    public function test_khong_truyen_khoang_ngay_thi_khong_gan_available_range(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 10]);

        $result = $this->service()->adminIndexWithAvailability();

        $row = $result->firstWhere('id', $roomType->id);

        $this->assertNotNull($row->available_today);
        $this->assertObjectNotHasProperty('available_range', $row);
    }

    public function test_booking_giao_mot_phan_khoang_loc_thi_tru_dung_so_phong(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 10]);

        // Booking 10/12 - 15/12, khoảng lọc 12/12 - 20/12 -> giao nhau
        $booking = Booking::factory()->create([
            'check_in'  => '2026-12-10',
            'check_out' => '2026-12-15',
            'status'    => BookingStatus::CONFIRMED,
        ]);
        BookingItem::factory()->create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'quantity'     => 3,
        ]);

        $result = $this->service()->adminIndexWithAvailability('2026-12-12', '2026-12-20');

        $row = $result->firstWhere('id', $roomType->id);

        $this->assertSame(7, $row->available_range);
    }

    public function test_booking_khong_giao_khoang_loc_thi_khong_bi_tru(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 10]);

        // Booking hoàn toàn nằm ngoài khoảng lọc -> không được trừ
        $booking = Booking::factory()->create([
            'check_in'  => '2026-01-01',
            'check_out' => '2026-01-05',
            'status'    => BookingStatus::CONFIRMED,
        ]);
        BookingItem::factory()->create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'quantity'     => 5,
        ]);

        $result = $this->service()->adminIndexWithAvailability('2026-12-12', '2026-12-20');

        $row = $result->firstWhere('id', $roomType->id);

        $this->assertSame(10, $row->available_range);
    }

    public function test_booking_sat_ngay_khong_tinh_la_giao_nhau(): void
    {
        // Booking trả phòng 12/12, khoảng lọc bắt đầu đúng 12/12 -> không
        // giao nhau (khách trả sáng, khách mới nhận cùng ngày vẫn được).
        $roomType = RoomType::factory()->create(['total_rooms' => 10]);

        $booking = Booking::factory()->create([
            'check_in'  => '2026-12-08',
            'check_out' => '2026-12-12',
            'status'    => BookingStatus::CONFIRMED,
        ]);
        BookingItem::factory()->create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'quantity'     => 5,
        ]);

        $result = $this->service()->adminIndexWithAvailability('2026-12-12', '2026-12-20');

        $row = $result->firstWhere('id', $roomType->id);

        $this->assertSame(10, $row->available_range);
    }

    public function test_booking_da_huy_khong_tinh_vao_so_phong_bi_giu(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 10]);

        $booking = Booking::factory()->cancelled()->create([
            'check_in'  => '2026-12-10',
            'check_out' => '2026-12-15',
        ]);
        BookingItem::factory()->create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'quantity'     => 5,
        ]);

        $result = $this->service()->adminIndexWithAvailability('2026-12-12', '2026-12-20');

        $row = $result->firstWhere('id', $roomType->id);

        $this->assertSame(10, $row->available_range);
    }

    public function test_booking_checked_out_khong_tinh_vao_so_phong_bi_giu(): void
    {
        // checked_out/completed = đã trả phòng xong, không còn giữ chỗ nữa
        // dù ngày check_out nằm trong tương lai giả định của khoảng lọc.
        $roomType = RoomType::factory()->create(['total_rooms' => 10]);

        $booking = Booking::factory()->create([
            'check_in'  => '2026-12-10',
            'check_out' => '2026-12-15',
            'status'    => BookingStatus::CHECKED_OUT,
        ]);
        BookingItem::factory()->create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'quantity'     => 5,
        ]);

        $result = $this->service()->adminIndexWithAvailability('2026-12-12', '2026-12-20');

        $row = $result->firstWhere('id', $roomType->id);

        $this->assertSame(10, $row->available_range);
    }

    public function test_khong_ra_so_am_khi_tong_quantity_dat_vuot_total_rooms(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);

        $bookingA = Booking::factory()->create([
            'check_in' => '2026-12-10', 'check_out' => '2026-12-15', 'status' => BookingStatus::CONFIRMED,
        ]);
        BookingItem::factory()->create(['booking_id' => $bookingA->id, 'room_type_id' => $roomType->id, 'quantity' => 4]);

        $bookingB = Booking::factory()->create([
            'check_in' => '2026-12-13', 'check_out' => '2026-12-18', 'status' => BookingStatus::PENDING,
        ]);
        BookingItem::factory()->create(['booking_id' => $bookingB->id, 'room_type_id' => $roomType->id, 'quantity' => 4]);

        $result = $this->service()->adminIndexWithAvailability('2026-12-12', '2026-12-20');

        $row = $result->firstWhere('id', $roomType->id);

        // 4 + 4 = 8 > total_rooms 5 -> không được ra số âm (-3)
        $this->assertSame(0, $row->available_range);
    }

    public function test_available_today_khong_doi_khi_co_truyen_khoang_loc(): void
    {
        // Đảm bảo thêm available_range không làm sai lệch available_today cũ.
        $roomType = RoomType::factory()->create(['total_rooms' => 10]);

        $result = $this->service()->adminIndexWithAvailability('2026-12-12', '2026-12-20');

        $row = $result->firstWhere('id', $roomType->id);

        $this->assertSame(10, $row->available_today);
    }
}
