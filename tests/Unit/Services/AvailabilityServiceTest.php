<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use App\Services\AvailabilityService;
use App\Services\DateRangeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Tuần 9 - Sprint 5: Test AvailabilityService — toàn bộ ca overlap ngày và
 * các điều kiện giữ phòng (holding statuses).
 *
 * Ngày dùng trong test đều tính TƯƠNG ĐỐI so với hôm nay (xem d()) thay vì
 * hardcode ngày tuyệt đối — DateRangeService::validate() chặn check_in
 * trước hôm nay, nên ngày hardcode cố định sẽ tự nhiên "hết hạn" và làm
 * test fail dù code không đổi gì, chỉ vì thời gian thật trôi qua.
 *
 * Test case ID | Chức năng                                    | Kết quả mong đợi
 * TC-AVA-001   | Không có booking nào giao nhau                | available_quantity = total_rooms
 * TC-AVA-002   | Trùng hoàn toàn (exact match)                 | Trừ đúng quantity đã đặt
 * TC-AVA-003   | Giao đầu (existing kết thúc trong khoảng mới) | Trừ đúng quantity đã đặt
 * TC-AVA-004   | Giao cuối (existing bắt đầu trong khoảng mới) | Trừ đúng quantity đã đặt
 * TC-AVA-005   | Khoảng mới nằm trong khoảng đã đặt            | Trừ đúng quantity đã đặt
 * TC-AVA-006   | Khoảng mới bao trọn khoảng đã đặt             | Trừ đúng quantity đã đặt
 * TC-AVA-007   | Hai khoảng sát nhau (trả/nhận cùng ngày)      | Không tính là giao nhau
 * TC-AVA-008   | Booking pending/confirmed/checked_in          | Đều giữ phòng
 * TC-AVA-009   | Booking cancelled                             | Không giữ phòng
 * TC-AVA-010   | Hết phòng (booked = total_rooms)              | can_book = false, available = 0
 * TC-AVA-011   | Nhiều booking giao nhau cộng dồn quantity      | Trừ đúng tổng quantity
 * TC-AVA-012   | Booking ở room_type khác không ảnh hưởng       | available_quantity không đổi
 * TC-AVA-013   | Room type inactive/không tồn tại               | Ném ModelNotFoundException
 * TC-AVA-014   | Ngày không hợp lệ                             | Ném ValidationException
 */
class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AvailabilityService(new DateRangeService());
    }

    /**
     * Ngày tương đối so với hôm nay + offset an toàn 30 ngày, giữ nguyên
     * khoảng cách tương đối giữa các mốc ngày trong từng test case.
     */
    private function d(int $offsetDays): string
    {
        return now()->addDays(30 + $offsetDays)->toDateString();
    }

    private function createBooking(
        RoomType $roomType,
        string $checkIn,
        string $checkOut,
        int $quantity = 1,
        string $status = 'pending'
    ): Booking {
        $booking = Booking::create([
            'booking_code'   => 'TEST-' . uniqid(),
            'check_in'       => $checkIn,
            'check_out'      => $checkOut,
            'nights'         => (new \DateTime($checkIn))->diff(new \DateTime($checkOut))->days,
            'customer_name'  => 'Khách Test',
            'customer_phone' => '0900000000',
            'total_amount'   => 0,
            'status'         => $status,
        ]);

        BookingItem::create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomType->id,
            'quantity'        => $quantity,
            'price_per_night' => $roomType->price_per_night,
            'nights'          => $booking->nights,
            'subtotal'        => 0,
        ]);

        return $booking;
    }

    public function test_no_overlapping_booking_leaves_all_rooms_available(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);

        $result = $this->service->check($roomType->id, $this->d(5), $this->d(7), 1);

        $this->assertSame(5, $result['available_quantity']);
        $this->assertTrue($result['can_book']);
    }

    public function test_exact_match_overlap_reduces_availability(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);
        $this->createBooking($roomType, $this->d(5), $this->d(7), 2);

        $result = $this->service->check($roomType->id, $this->d(5), $this->d(7), 1);

        $this->assertSame(3, $result['available_quantity']);
    }

    public function test_overlap_at_start_of_new_range(): void
    {
        // Existing: d3 -> d6, New: d5 -> d9 (existing kết thúc trong khoảng mới)
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);
        $this->createBooking($roomType, $this->d(3), $this->d(6), 2);

        $result = $this->service->check($roomType->id, $this->d(5), $this->d(9), 1);

        $this->assertSame(3, $result['available_quantity']);
    }

    public function test_overlap_at_end_of_new_range(): void
    {
        // Existing: d8 -> d11, New: d5 -> d9 (existing bắt đầu trong khoảng mới)
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);
        $this->createBooking($roomType, $this->d(8), $this->d(11), 2);

        $result = $this->service->check($roomType->id, $this->d(5), $this->d(9), 1);

        $this->assertSame(3, $result['available_quantity']);
    }

    public function test_new_range_contained_within_existing_booking(): void
    {
        // Existing: d0 -> d15, New: d5 -> d7 (nằm trong)
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);
        $this->createBooking($roomType, $this->d(0), $this->d(15), 2);

        $result = $this->service->check($roomType->id, $this->d(5), $this->d(7), 1);

        $this->assertSame(3, $result['available_quantity']);
    }

    public function test_new_range_encompasses_existing_booking(): void
    {
        // Existing: d5 -> d7, New: d0 -> d15 (bao ngoài)
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);
        $this->createBooking($roomType, $this->d(5), $this->d(7), 2);

        $result = $this->service->check($roomType->id, $this->d(0), $this->d(15), 1);

        $this->assertSame(3, $result['available_quantity']);
    }

    public function test_adjacent_ranges_do_not_overlap(): void
    {
        // Existing: d0 -> d5 (trả phòng), New: d5 -> d10 (nhận phòng cùng ngày)
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);
        $this->createBooking($roomType, $this->d(0), $this->d(5), 5);

        $result = $this->service->check($roomType->id, $this->d(5), $this->d(10), 1);

        $this->assertSame(5, $result['available_quantity']);
    }

    public function test_pending_confirmed_and_checked_in_bookings_all_hold_rooms(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);
        $this->createBooking($roomType, $this->d(5), $this->d(7), 1, 'pending');
        $this->createBooking($roomType, $this->d(5), $this->d(7), 1, 'confirmed');
        $this->createBooking($roomType, $this->d(5), $this->d(7), 1, 'checked_in');

        $result = $this->service->check($roomType->id, $this->d(5), $this->d(7), 1);

        $this->assertSame(2, $result['available_quantity']);
    }

    public function test_cancelled_booking_does_not_hold_room(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);
        $this->createBooking($roomType, $this->d(5), $this->d(7), 3, 'cancelled');

        $result = $this->service->check($roomType->id, $this->d(5), $this->d(7), 1);

        $this->assertSame(5, $result['available_quantity']);
    }

    public function test_sold_out_when_booked_quantity_equals_total_rooms(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 3]);
        $this->createBooking($roomType, $this->d(5), $this->d(7), 3, 'confirmed');

        $result = $this->service->check($roomType->id, $this->d(5), $this->d(7), 1);

        $this->assertSame(0, $result['available_quantity']);
        $this->assertFalse($result['can_book']);
    }

    public function test_multiple_overlapping_bookings_accumulate_quantity(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);
        $this->createBooking($roomType, $this->d(3), $this->d(6), 1, 'pending');
        $this->createBooking($roomType, $this->d(4), $this->d(8), 1, 'confirmed');
        $this->createBooking($roomType, $this->d(0), $this->d(15), 1, 'checked_in');

        $result = $this->service->check($roomType->id, $this->d(5), $this->d(7), 1);

        $this->assertSame(2, $result['available_quantity']);
    }

    public function test_booking_on_other_room_type_does_not_affect_availability(): void
    {
        $roomType      = RoomType::factory()->create(['total_rooms' => 5]);
        $otherRoomType = RoomType::factory()->create(['total_rooms' => 5]);
        $this->createBooking($otherRoomType, $this->d(5), $this->d(7), 5, 'confirmed');

        $result = $this->service->check($roomType->id, $this->d(5), $this->d(7), 1);

        $this->assertSame(5, $result['available_quantity']);
    }

    public function test_hidden_room_type_throws_not_found_exception(): void
    {
        $roomType = RoomType::factory()->hidden()->create();

        $this->expectException(ModelNotFoundException::class);
        $this->service->check($roomType->id, $this->d(5), $this->d(7), 1);
    }

    public function test_nonexistent_room_type_throws_not_found_exception(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->service->check(999999, $this->d(5), $this->d(7), 1);
    }

    public function test_invalid_date_range_throws_validation_exception(): void
    {
        $roomType = RoomType::factory()->create();

        $this->expectException(ValidationException::class);
        $this->service->check($roomType->id, $this->d(7), $this->d(5), 1);
    }

    public function test_can_book_returns_false_when_quantity_exceeds_availability(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 2]);
        $this->createBooking($roomType, $this->d(5), $this->d(7), 1, 'confirmed');

        $this->assertFalse($this->service->canBook($roomType->id, $this->d(5), $this->d(7), 2));
        $this->assertTrue($this->service->canBook($roomType->id, $this->d(5), $this->d(7), 1));
    }
}
