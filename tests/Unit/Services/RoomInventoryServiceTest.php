<?php

namespace Tests\Unit\Services;

use App\Models\RoomType;
use App\Services\RoomInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Test case ID | Chức năng                                  | Kết quả mong đợi
 * TC-INV-001   | Lấy room type active                       | Trả về room type
 * TC-INV-002   | getPricingData trả đúng base_price/total_rooms | Dữ liệu khớp room type
 * TC-INV-003   | Tổng phòng active chỉ tính phòng active     | Không cộng phòng hidden/maintenance
 * TC-INV-004   | Phòng status=hidden                        | Ném ValidationException
 * TC-INV-005   | Phòng status=maintenance                   | Ném ValidationException
 * TC-INV-006   | Phòng đã bị xóa mềm                        | Ném ValidationException
 * TC-INV-007   | total_rooms = 0                            | Ném ValidationException
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

    public function test_returns_bookable_room_type_when_active(): void
    {
        $roomType = RoomType::factory()->create(['status' => 'active', 'total_rooms' => 10]);

        $result = $this->service->getBookableRoomType($roomType->id);

        $this->assertSame($roomType->id, $result->id);
    }

    public function test_pricing_data_matches_room_type_fields(): void
    {
        $roomType = RoomType::factory()->create([
            'status'          => 'active',
            'total_rooms'     => 7,
            'price_per_night' => 850000,
            'capacity'        => 3,
        ]);

        $data = $this->service->getPricingData($roomType->id);

        $this->assertSame($roomType->id, $data['room_type_id']);
        $this->assertSame(850000.0, $data['base_price']);
        $this->assertSame(7, $data['total_rooms']);
        $this->assertSame(3, $data['capacity']);
    }

    public function test_total_active_rooms_excludes_inactive_room_types(): void
    {
        RoomType::factory()->create(['status' => 'active', 'total_rooms' => 10]);
        RoomType::factory()->create(['status' => 'active', 'total_rooms' => 5]);
        RoomType::factory()->hidden()->create(['total_rooms' => 99]);
        RoomType::factory()->maintenance()->create(['total_rooms' => 99]);

        $total = $this->service->getTotalActiveRooms();

        $this->assertSame(15, $total);
    }

    public function test_hidden_room_type_is_not_bookable(): void
    {
        $roomType = RoomType::factory()->hidden()->create();

        $this->expectException(ValidationException::class);
        $this->service->getBookableRoomType($roomType->id);
    }

    public function test_maintenance_room_type_is_not_bookable(): void
    {
        $roomType = RoomType::factory()->maintenance()->create();

        $this->expectException(ValidationException::class);
        $this->service->getBookableRoomType($roomType->id);
    }

    public function test_soft_deleted_room_type_is_not_bookable(): void
    {
        $roomType = RoomType::factory()->create(['status' => 'active']);
        $roomType->delete();

        $this->expectException(ValidationException::class);
        $this->service->getBookableRoomType($roomType->id);
    }

    public function test_zero_total_rooms_is_invalid(): void
    {
        $roomType = RoomType::factory()->create(['status' => 'active', 'total_rooms' => 0]);

        $this->expectException(ValidationException::class);
        $this->service->assertValidTotalRooms($roomType);
    }
}
