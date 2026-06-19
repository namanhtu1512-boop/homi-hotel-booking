<?php

namespace Tests\Unit\Services;

use App\Models\Hotel;
use App\Models\RoomType;
use App\Services\RoomInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Test case ID | Chức năng                                  | Kết quả mong đợi
 * TC-INV-001   | Lấy room type active, hotel active         | Trả về room type
 * TC-INV-002   | getPricingData trả đúng base_price/total_rooms | Dữ liệu khớp room type
 * TC-INV-003   | Tổng phòng theo hotel chỉ tính phòng active | Không cộng phòng hidden
 * TC-INV-004   | Phòng status=hidden                        | Ném ValidationException
 * TC-INV-005   | Phòng status=maintenance                   | Ném ValidationException
 * TC-INV-006   | Phòng thuộc hotel đang ẩn                  | Ném ValidationException
 * TC-INV-007   | Phòng đã bị xóa mềm                        | Ném ValidationException
 * TC-INV-008   | total_rooms = 0                            | Ném ValidationException
 */
class RoomInventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoomInventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RoomInventoryService();
    }

    public function test_returns_bookable_room_type_when_active_and_hotel_active(): void
    {
        $hotel    = Hotel::factory()->create(['status' => 'active']);
        $roomType = RoomType::factory()->create(['hotel_id' => $hotel->id, 'status' => 'active', 'total_rooms' => 10]);

        $result = $this->service->getBookableRoomType($roomType->id);

        $this->assertSame($roomType->id, $result->id);
    }

    public function test_pricing_data_matches_room_type_fields(): void
    {
        $hotel    = Hotel::factory()->create(['status' => 'active']);
        $roomType = RoomType::factory()->create([
            'hotel_id'        => $hotel->id,
            'status'          => 'active',
            'total_rooms'     => 7,
            'price_per_night' => 850000,
            'capacity'        => 3,
        ]);

        $data = $this->service->getPricingData($roomType->id);

        $this->assertSame($roomType->id, $data['room_type_id']);
        $this->assertSame($hotel->id, $data['hotel_id']);
        $this->assertSame(850000.0, $data['base_price']);
        $this->assertSame(7, $data['total_rooms']);
        $this->assertSame(3, $data['capacity']);
    }

    public function test_total_rooms_by_hotel_excludes_inactive_room_types(): void
    {
        $hotel = Hotel::factory()->create(['status' => 'active']);
        RoomType::factory()->create(['hotel_id' => $hotel->id, 'status' => 'active', 'total_rooms' => 10]);
        RoomType::factory()->create(['hotel_id' => $hotel->id, 'status' => 'active', 'total_rooms' => 5]);
        RoomType::factory()->hidden()->create(['hotel_id' => $hotel->id, 'total_rooms' => 99]);
        RoomType::factory()->maintenance()->create(['hotel_id' => $hotel->id, 'total_rooms' => 99]);

        $total = $this->service->getTotalRoomsByHotel($hotel->id);

        $this->assertSame(15, $total);
    }

    public function test_hidden_room_type_is_not_bookable(): void
    {
        $hotel    = Hotel::factory()->create(['status' => 'active']);
        $roomType = RoomType::factory()->hidden()->create(['hotel_id' => $hotel->id]);

        $this->expectException(ValidationException::class);
        $this->service->getBookableRoomType($roomType->id);
    }

    public function test_maintenance_room_type_is_not_bookable(): void
    {
        $hotel    = Hotel::factory()->create(['status' => 'active']);
        $roomType = RoomType::factory()->maintenance()->create(['hotel_id' => $hotel->id]);

        $this->expectException(ValidationException::class);
        $this->service->getBookableRoomType($roomType->id);
    }

    public function test_room_type_under_hidden_hotel_is_not_bookable(): void
    {
        $hotel    = Hotel::factory()->create(['status' => 'hidden']);
        $roomType = RoomType::factory()->create(['hotel_id' => $hotel->id, 'status' => 'active']);

        $this->expectException(ValidationException::class);
        $this->service->getBookableRoomType($roomType->id);
    }

    public function test_soft_deleted_room_type_is_not_bookable(): void
    {
        $hotel    = Hotel::factory()->create(['status' => 'active']);
        $roomType = RoomType::factory()->create(['hotel_id' => $hotel->id, 'status' => 'active']);
        $roomType->delete();

        $this->expectException(ValidationException::class);
        $this->service->getBookableRoomType($roomType->id);
    }

    public function test_zero_total_rooms_is_invalid(): void
    {
        $hotel    = Hotel::factory()->create(['status' => 'active']);
        $roomType = RoomType::factory()->create(['hotel_id' => $hotel->id, 'status' => 'active', 'total_rooms' => 0]);

        $this->expectException(ValidationException::class);
        $this->service->assertValidTotalRooms($roomType);
    }
}
