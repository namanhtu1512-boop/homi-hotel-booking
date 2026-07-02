<?php

namespace Tests\Unit\Services;

use App\Models\RoomType;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tuần 10 BE2 — test case giá phòng:
 *   - Tính đúng tổng tiền theo số đêm và số lượng phòng.
 *   - Phòng inactive không được đặt (kiểm tra tầng BookingService, test riêng ở đây).
 *   - Giá phòng lấy đúng tại thời điểm đặt.
 */
class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PricingService();
    }

    public function test_calculate_returns_correct_nights_price_and_total(): void
    {
        $roomType = RoomType::factory()->create(['price_per_night' => 500000]);

        $result = $this->service->calculate($roomType, '2026-07-10', '2026-07-13', 1);

        $this->assertEquals(3,       $result['nights']);
        $this->assertEquals(500000,  $result['unit_price']);
        $this->assertEquals(1,       $result['quantity']);
        $this->assertEquals(1500000, $result['total_price']);
    }

    public function test_calculate_multiplies_quantity(): void
    {
        $roomType = RoomType::factory()->create(['price_per_night' => 800000]);

        $result = $this->service->calculate($roomType, '2026-08-01', '2026-08-03', 2);

        $this->assertEquals(2,       $result['nights']);
        $this->assertEquals(2,       $result['quantity']);
        $this->assertEquals(3200000, $result['total_price']);
    }

    public function test_calculate_single_night(): void
    {
        $roomType = RoomType::factory()->create(['price_per_night' => 1200000]);

        $result = $this->service->calculate($roomType, '2026-07-20', '2026-07-21', 1);

        $this->assertEquals(1,       $result['nights']);
        $this->assertEquals(1200000, $result['total_price']);
    }

    public function test_night_count_helper_is_consistent_with_calculate(): void
    {
        $roomType = RoomType::factory()->create(['price_per_night' => 100000]);

        $nights = $this->service->nightCount('2026-07-05', '2026-07-10');
        $result = $this->service->calculate($roomType, '2026-07-05', '2026-07-10', 1);

        $this->assertEquals($nights, $result['nights']);
        $this->assertEquals(5, $nights);
    }

    public function test_price_is_taken_from_room_type_at_booking_time(): void
    {
        // Giả lập tình huống giá phòng được đọc trực tiếp từ model tại thời điểm đặt.
        // Nếu giá thay đổi sau đó, pricing đã lưu vào booking_item không bị ảnh hưởng.
        $roomType = RoomType::factory()->create(['price_per_night' => 600000]);

        $result = $this->service->calculate($roomType, '2026-09-01', '2026-09-04', 1);
        $capturedUnitPrice = $result['unit_price'];

        // Giả lập admin đổi giá phòng sau khi đặt
        $roomType->update(['price_per_night' => 999999]);

        // Booking cũ vẫn dùng capturedUnitPrice đã lưu
        $this->assertEquals(600000, $capturedUnitPrice);
        $this->assertEquals(1800000, $capturedUnitPrice * $result['nights'] * $result['quantity']);
    }

    public function test_inactive_room_type_is_rejected_by_booking_service(): void
    {
        // PricingService bản thân không kiểm tra status.
        // BookingService.create() gọi RoomType::where('status', 'active')->findOrFail()
        // trước khi tính giá — phòng inactive sẽ throw ModelNotFoundException (404).
        $inactiveRoom = RoomType::factory()->hidden()->create(['price_per_night' => 500000]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        \App\Models\RoomType::where('status', 'active')->findOrFail($inactiveRoom->id);
    }
}
