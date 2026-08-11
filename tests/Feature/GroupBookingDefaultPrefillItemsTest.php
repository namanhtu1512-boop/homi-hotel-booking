<?php

namespace Tests\Feature;

use App\Models\GroupBookingRequest;
use App\Models\RoomType;
use App\Models\User;
use App\Services\GroupBookingRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupBookingDefaultPrefillItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_uu_tien_so_luong_phong_theo_phuong_an_khach_da_chon(): void
    {
        $standard = RoomType::factory()->create(['name' => 'Phòng Standard', 'capacity' => 2]);
        $superior = RoomType::factory()->create(['name' => 'Phòng Superior', 'capacity' => 2]);

        $groupRequest = GroupBookingRequest::factory()->create([
            'room_type_ids'       => [$standard->id, $superior->id],
            'selected_suggestion' => [
                'label' => 'Phương án tiết kiệm',
                'rooms' => [
                    ['room_type_id' => $standard->id, 'room_type' => 'Phòng Standard', 'quantity' => 15, 'occupancy_each' => 2, 'price_each' => 900000],
                    ['room_type_id' => $superior->id, 'room_type' => 'Phòng Superior', 'quantity' => 3, 'occupancy_each' => 2, 'price_each' => 1100000],
                ],
                'estimated_total_price' => 16800000,
            ],
        ]);

        $items = app(GroupBookingRequestService::class)->defaultPrefillItems($groupRequest);

        $this->assertSame([
            ['room_type_id' => $standard->id, 'quantity' => 15, 'adults' => 2, 'children' => 0, 'infants' => 0],
            ['room_type_id' => $superior->id, 'quantity' => 3, 'adults' => 2, 'children' => 0, 'infants' => 0],
        ], $items);
    }

    public function test_khong_co_selected_suggestion_thi_roi_ve_room_type_ids_moi_loai_1_phong(): void
    {
        $standard = RoomType::factory()->create();

        $groupRequest = GroupBookingRequest::factory()->create([
            'room_type_ids'       => [$standard->id],
            'selected_suggestion' => null,
        ]);

        $items = app(GroupBookingRequestService::class)->defaultPrefillItems($groupRequest);

        $this->assertSame([
            ['room_type_id' => $standard->id, 'quantity' => 1, 'adults' => 2, 'children' => 0, 'infants' => 0],
        ], $items);
    }

    public function test_trang_tao_don_admin_hien_dung_so_luong_theo_goi_y_khach_chon(): void
    {
        $admin    = User::factory()->admin()->create();
        $standard = RoomType::factory()->create(['name' => 'Phòng Standard']);

        $groupRequest = GroupBookingRequest::factory()->create([
            'user_id'             => User::factory()->create(['role' => 'customer'])->id,
            'room_type_ids'       => [$standard->id],
            'selected_suggestion' => [
                'label' => 'Phương án tiết kiệm',
                'rooms' => [
                    ['room_type_id' => $standard->id, 'room_type' => 'Phòng Standard', 'quantity' => 15, 'occupancy_each' => 2, 'price_each' => 900000],
                ],
                'estimated_total_price' => 13500000,
            ],
        ]);

        $response = $this->actingAs($admin)->withSession(['login_context' => 'admin'])
            ->get(route('admin.group-bookings.show', $groupRequest->id));

        $response->assertOk();
        $response->assertSee('name="items[0][quantity]" class="input" min="1" value="15"', false);
        $response->assertSee('name="quote_items[0][quantity]" class="input" min="1" value="15"', false);
    }
}
