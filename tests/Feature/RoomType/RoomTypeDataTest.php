<?php

namespace Tests\Feature\RoomType;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use App\Models\User;
use App\Services\RoomTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * BE4 - Tuần 6: Test dữ liệu module Room Types
 * Phạm vi: CRUD dữ liệu phòng, validation, ảnh, giá, số lượng, trạng thái,
 *          soft-delete vs hidden khi có booking đang hoạt động.
 *
 * Phân quyền (admin/staff/customer/anonymous) đã có ở AdminRoomTypeAccessTest.php,
 * nên test này KHÔNG lặp lại các ca phân quyền — chỉ test dữ liệu và logic
 * nghiệp vụ thực tế của RoomTypeService.
 *
 * Vì hệ thống chỉ vận hành 1 khách sạn duy nhất, room_types không còn
 * hotel_id và route không còn scope theo hotelId.
 *
 * Chạy: php artisan test --filter=RoomTypeDataTest
 */
class RoomTypeDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected RoomTypeService $roomTypeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['status' => 'active']);
        $this->roomTypeService = app(RoomTypeService::class);
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function makeRoomType(array $attrs = []): RoomType
    {
        return RoomType::factory()->create(array_merge(['status' => 'active'], $attrs));
    }

    private function validPayload(array $override = []): array
    {
        return array_merge([
            'name'            => 'Phòng Deluxe View Biển',
            'description'     => 'Phòng rộng rãi, view biển, đầy đủ tiện nghi.',
            'price_per_night' => 1200000,
            'capacity'        => 2,
            'bed_type'        => '1 giường đôi lớn',
            'area'            => 32,
            'total_rooms'     => 8,
        ], $override);
    }

    /**
     * Tạo booking + booking_item thủ công để mô phỏng "loại phòng đang có
     * booking active".
     */
    private function makeActiveBookingFor(RoomType $roomType, string $status = 'pending'): Booking
    {
        $booking = Booking::create([
            'booking_code'   => 'BK-' . strtoupper(uniqid()),
            'check_in'       => now()->addDays(5),
            'check_out'      => now()->addDays(7),
            'nights'         => 2,
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
            'total_amount'   => $roomType->price_per_night * 2,
            'status'         => $status,
        ]);

        BookingItem::create([
            'booking_id'       => $booking->id,
            'room_type_id'     => $roomType->id,
            'quantity'         => 1,
            'price_per_night'  => $roomType->price_per_night,
            'nights'           => 2,
            'subtotal'         => $roomType->price_per_night * 2,
        ]);

        return $booking;
    }

    // =========================================================
    // TC-RTD-001: Tạo loại phòng - dữ liệu đúng được lưu chính xác
    // =========================================================

    /** @test */
    public function test_TC_RTD_001_create_room_type_persists_correct_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Phòng Deluxe View Biển')
            ->assertJsonPath('data.capacity', 2)
            ->assertJsonPath('data.total_rooms', 8)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('room_types', [
            'name'        => 'Phòng Deluxe View Biển',
            'capacity'    => 2,
            'total_rooms' => 8,
            'status'      => 'active',
        ]);
    }

    /** @test */
    public function test_TC_RTD_002_create_room_type_auto_generates_slug(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $this->validPayload([
                'name' => 'Phòng Gia Đình Cao Cấp',
            ]));

        $response->assertCreated();
        $this->assertDatabaseHas('room_types', [
            'name' => 'Phòng Gia Đình Cao Cấp',
            'slug' => 'phong-gia-dinh-cao-cap',
        ]);
    }

    /** @test */
    public function test_TC_RTD_003_duplicate_name_gets_unique_slug_suffix(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $this->validPayload(['name' => 'Standard Room']))
            ->assertCreated();

        // Slug giờ là duy nhất toàn hệ thống (chỉ 1 khách sạn) -> tên trùng vẫn
        // tạo được nhưng slug tự thêm hậu tố -2.
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $this->validPayload(['name' => 'Standard Room']))
            ->assertCreated();

        $this->assertDatabaseHas('room_types', ['name' => 'Standard Room', 'slug' => 'standard-room']);
        $this->assertDatabaseHas('room_types', ['name' => 'Standard Room', 'slug' => 'standard-room-2']);
    }

    // =========================================================
    // TC-RTD-010: Validation
    // =========================================================

    /** @test */
    public function test_TC_RTD_010_missing_name_returns_validation_error(): void
    {
        $payload = $this->validPayload();
        unset($payload['name']);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function test_TC_RTD_011_negative_price_returns_validation_error(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $this->validPayload([
                'price_per_night' => -100000,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price_per_night']);
    }

    /** @test */
    public function test_TC_RTD_012_non_numeric_price_returns_validation_error(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $this->validPayload([
                'price_per_night' => 'không phải số',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price_per_night']);
    }

    /** @test */
    public function test_TC_RTD_013_capacity_zero_returns_validation_error(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $this->validPayload([
                'capacity' => 0,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['capacity']);
    }

    /** @test */
    public function test_TC_RTD_014_capacity_negative_returns_validation_error(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $this->validPayload([
                'capacity' => -2,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['capacity']);
    }

    /** @test */
    public function test_TC_RTD_015_total_rooms_zero_returns_validation_error(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $this->validPayload([
                'total_rooms' => 0,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['total_rooms']);
    }

    /** @test */
    public function test_TC_RTD_016_total_rooms_negative_returns_validation_error(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $this->validPayload([
                'total_rooms' => -5,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['total_rooms']);
    }

    /** @test */
    public function test_TC_RTD_017_name_too_long_returns_validation_error(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $this->validPayload([
                'name' => str_repeat('A', 256),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function test_TC_RTD_018_missing_capacity_returns_validation_error(): void
    {
        $payload = $this->validPayload();
        unset($payload['capacity']);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['capacity']);
    }

    /** @test */
    public function test_TC_RTD_019_missing_total_rooms_returns_validation_error(): void
    {
        $payload = $this->validPayload();
        unset($payload['total_rooms']);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['total_rooms']);
    }

    /** @test */
    public function test_TC_RTD_020_validation_messages_are_in_vietnamese(): void
    {
        $payload = $this->validPayload();
        unset($payload['name']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $payload);

        $response->assertUnprocessable();
        $message = $response->json('errors.name.0');
        $this->assertStringContainsString('tên loại phòng', mb_strtolower($message));
    }

    // =========================================================
    // TC-RTD-030: Cập nhật giá phòng (RoomTypeService::updatePrice)
    // =========================================================

    /** @test */
    public function test_TC_RTD_030_update_price_persists_new_value(): void
    {
        $roomType = $this->makeRoomType(['price_per_night' => 500000]);

        $this->roomTypeService->updatePrice($roomType, 950000);

        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'price_per_night' => 950000]);
    }

    /** @test */
    public function test_TC_RTD_031_update_price_missing_value_returns_422(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/room-types/{$roomType->id}/price", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price_per_night']);
    }

    /** @test */
    public function test_TC_RTD_032_update_price_negative_returns_422(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/room-types/{$roomType->id}/price", ['price_per_night' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price_per_night']);
    }

    /** @test */
    public function test_TC_RTD_033_update_price_to_zero_is_allowed(): void
    {
        // price_per_night min:0 cho phép 0 (phòng khuyến mãi/miễn phí thử nghiệm)
        $roomType = $this->makeRoomType();

        $this->roomTypeService->updatePrice($roomType, 0);

        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'price_per_night' => 0]);
    }

    // =========================================================
    // TC-RTD-040: Cập nhật số lượng phòng (RoomTypeService::updateInventory)
    // =========================================================

    /** @test */
    public function test_TC_RTD_040_update_inventory_persists_new_value(): void
    {
        $roomType = $this->makeRoomType(['total_rooms' => 5]);

        $this->roomTypeService->updateInventory($roomType, 15);

        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'total_rooms' => 15]);
    }

    /** @test */
    public function test_TC_RTD_041_update_inventory_zero_throws_validation_exception(): void
    {
        $roomType = $this->makeRoomType();

        $this->expectException(ValidationException::class);
        $this->roomTypeService->updateInventory($roomType, 0);
    }

    /** @test */
    public function test_TC_RTD_042_update_inventory_negative_throws_validation_exception(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/room-types/{$roomType->id}/inventory", ['total_rooms' => -3])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['total_rooms']);
    }

    /** @test */
    public function test_TC_RTD_043_update_inventory_non_integer_returns_422(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/room-types/{$roomType->id}/inventory", ['total_rooms' => 'năm phòng'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['total_rooms']);
    }

    /** @test */
    public function test_TC_RTD_044_update_inventory_missing_returns_422(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/room-types/{$roomType->id}/inventory", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['total_rooms']);
    }

    // =========================================================
    // TC-RTD-050: Cập nhật toàn bộ thông tin (PUT /admin/room-types/{id})
    // =========================================================

    /** @test */
    public function test_TC_RTD_050_update_partial_fields_only_changes_given_fields(): void
    {
        $roomType = $this->makeRoomType([
            'name'     => 'Tên Cũ',
            'capacity' => 2,
        ]);

        $this->actingAs($this->admin)
            ->put("/admin/room-types/{$roomType->id}", $this->validPayload(['name' => 'Tên Cũ', 'capacity' => 4]))
            ->assertRedirect(route('admin.room-types.index'));

        $this->assertDatabaseHas('room_types', [
            'id'       => $roomType->id,
            'name'     => 'Tên Cũ',
            'capacity' => 4,
        ]);
    }

    /** @test */
    public function test_TC_RTD_051_update_name_regenerates_slug(): void
    {
        $roomType = $this->makeRoomType(['name' => 'Phòng Cũ', 'slug' => 'phong-cu']);

        $this->actingAs($this->admin)
            ->put("/admin/room-types/{$roomType->id}", $this->validPayload(['name' => 'Phòng Mới Sang Trọng']))
            ->assertRedirect();

        $this->assertDatabaseHas('room_types', [
            'id'   => $roomType->id,
            'slug' => 'phong-moi-sang-trong',
        ]);
    }

    /** @test */
    public function test_TC_RTD_052_update_total_rooms_to_zero_returns_validation_error(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->admin)
            ->put("/admin/room-types/{$roomType->id}", $this->validPayload(['total_rooms' => 0]))
            ->assertSessionHasErrors();
    }

    // =========================================================
    // TC-RTD-060: Xóa loại phòng — soft delete vs chuyển hidden
    // =========================================================

    /** @test */
    public function test_TC_RTD_060_delete_room_type_without_active_booking_soft_deletes(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->admin)
            ->delete("/admin/room-types/{$roomType->id}")
            ->assertRedirect(route('admin.room-types.index'));

        $this->assertSoftDeleted('room_types', ['id' => $roomType->id]);
    }

    /** @test */
    public function test_TC_RTD_061_delete_room_type_with_pending_booking_becomes_hidden_not_deleted(): void
    {
        $roomType = $this->makeRoomType();
        $this->makeActiveBookingFor($roomType, 'pending');

        $this->actingAs($this->admin)
            ->delete("/admin/room-types/{$roomType->id}")
            ->assertRedirect();

        // Không bị soft delete...
        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'deleted_at' => null]);
        // ...mà chuyển trạng thái hidden
        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'status' => 'hidden']);
    }

    /** @test */
    public function test_TC_RTD_062_delete_room_type_with_confirmed_booking_becomes_hidden(): void
    {
        $roomType = $this->makeRoomType();
        $this->makeActiveBookingFor($roomType, 'confirmed');

        $this->actingAs($this->admin)->delete("/admin/room-types/{$roomType->id}");

        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'status' => 'hidden']);
    }

    /** @test */
    public function test_TC_RTD_063_delete_room_type_with_only_cancelled_booking_soft_deletes(): void
    {
        // Booking đã hủy không tính là "active" -> vẫn cho soft delete bình thường
        $roomType = $this->makeRoomType();
        $this->makeActiveBookingFor($roomType, 'cancelled');

        $this->actingAs($this->admin)
            ->delete("/admin/room-types/{$roomType->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('room_types', ['id' => $roomType->id]);
    }

    /** @test */
    public function test_TC_RTD_064_deleted_room_type_excluded_from_active_listing(): void
    {
        $roomType = $this->makeRoomType();
        $roomType->delete();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/room-types');

        $this->assertNotContains($roomType->id, $ids);
    }

    // =========================================================
    // TC-RTD-070: Khôi phục loại phòng
    // =========================================================

    /** @test */
    public function test_TC_RTD_070_restore_brings_back_soft_deleted_room_type(): void
    {
        $roomType = $this->makeRoomType();
        $roomType->delete();

        $this->actingAs($this->admin)
            ->post("/admin/room-types/{$roomType->id}/restore")
            ->assertRedirect();

        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'deleted_at' => null]);
    }

    /** @test */
    public function test_TC_RTD_071_restore_nonexistent_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/room-types/999999/restore')
            ->assertNotFound();
    }

    // =========================================================
    // TC-RTD-080: Danh sách loại phòng (admin view)
    // =========================================================

    /** @test */
    public function test_TC_RTD_080_admin_list_includes_hidden_and_maintenance_room_types(): void
    {
        $this->makeRoomType(['status' => 'active']);
        $this->makeRoomType(['status' => 'hidden']);
        $this->makeRoomType(['status' => 'maintenance']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/room-types');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function test_TC_RTD_082_list_sorted_by_price_ascending(): void
    {
        $this->makeRoomType(['price_per_night' => 2000000]);
        $this->makeRoomType(['price_per_night' => 500000]);
        $this->makeRoomType(['price_per_night' => 1200000]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/room-types');

        $sorted = $prices;
        sort($sorted);

        $this->assertEquals($sorted, $prices);
    }

    // =========================================================
    // TC-RTD-090: Ảnh loại phòng (lưu đường dẫn ảnh qua textarea images_text)
    // =========================================================

    /** @test */
    public function test_TC_RTD_090_create_room_type_with_image_paths(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $this->validPayload([
                'images' => ['room-types/deluxe-1.jpg', 'room-types/deluxe-2.jpg'],
            ]));

        $response->assertCreated();
        $roomTypeId = $response->json('data.id');

        $this->assertDatabaseHas('room_type_images', [
            'room_type_id' => $roomType->id,
            'path'         => 'room-types/deluxe-1.jpg',
            'sort_order'   => 0,
        ]);
        $this->assertDatabaseHas('room_type_images', [
            'room_type_id' => $roomType->id,
            'path'         => 'room-types/deluxe-2.jpg',
            'sort_order'   => 1,
        ]);
    }

    /** @test */
    public function test_TC_RTD_091_create_room_type_image_path_too_long_returns_422(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/room-types', $this->validPayload([
                'images' => [str_repeat('a', 501) . '.jpg'],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['images.0']);
    }

    /** @test */
    public function test_TC_RTD_092_update_with_images_replaces_old_images(): void
    {
        $roomType = $this->makeRoomType();
        $roomType->images()->create(['path' => 'old-1.jpg', 'sort_order' => 0]);
        $roomType->images()->create(['path' => 'old-2.jpg', 'sort_order' => 1]);

        $this->actingAs($this->admin)
            ->put("/admin/room-types/{$roomType->id}", array_merge($this->validPayload(), [
                'images_text' => 'new-1.jpg',
            ]))
            ->assertRedirect();

        $this->assertDatabaseMissing('room_type_images', ['path' => 'old-1.jpg']);
        $this->assertDatabaseMissing('room_type_images', ['path' => 'old-2.jpg']);
        $this->assertDatabaseHas('room_type_images', ['room_type_id' => $roomType->id, 'path' => 'new-1.jpg']);
    }

    /** @test */
    public function test_TC_RTD_093_delete_single_image_reorders_remaining(): void
    {
        $roomType = $this->makeRoomType();
        $img1 = $roomType->images()->create(['path' => 'a.jpg', 'sort_order' => 0]);
        $img2 = $roomType->images()->create(['path' => 'b.jpg', 'sort_order' => 1]);
        $img3 = $roomType->images()->create(['path' => 'c.jpg', 'sort_order' => 2]);

        app(\App\Services\ImageService::class)->deleteRoomTypeImage($roomType, $img1->id);

        $this->assertDatabaseMissing('room_type_images', ['id' => $img1->id]);
        $this->assertDatabaseHas('room_type_images', ['id' => $img2->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('room_type_images', ['id' => $img3->id, 'sort_order' => 1]);
    }

    /** @test */
    public function test_TC_RTD_094_delete_nonexistent_image_returns_false(): void
    {
        $roomType = $this->makeRoomType();

        $result = app(\App\Services\ImageService::class)->deleteRoomTypeImage($roomType, 999999);

        $this->assertFalse($result);
    }

    // =========================================================
    // TC-RTD-100: Dữ liệu lớn — nhiều loại phòng
    // =========================================================

    /** @test */
    public function test_TC_RTD_100_lists_all_20_room_types_for_admin(): void
    {
        RoomType::factory()->count(20)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/room-types');

        $this->assertCount(20, $roomTypes);
    }

    /** @test */
    public function test_TC_RTD_101_response_time_under_500ms_with_20_room_types(): void
    {
        RoomType::factory()->count(20)->create();

        $start = microtime(true);
        $this->actingAs($this->admin)->getJson('/api/v1/admin/room-types');
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $elapsed, 'Trang danh sách 20 loại phòng phải tải dưới 500ms');
    }
}
