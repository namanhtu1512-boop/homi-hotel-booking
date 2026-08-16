<?php

namespace Tests\Feature\Staff;

use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomShowTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsStaff(): User
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff);
        session(['login_context' => 'staff']);

        return $staff;
    }

    public function test_xem_chi_tiet_phong(): void
    {
        $this->loginAsStaff();
        $roomType = RoomType::factory()->create();
        $room = Room::create(['room_type_id' => $roomType->id, 'room_number' => 'A101', 'housekeeping_status' => 'clean']);

        $response = $this->get(route('staff.rooms.show', $room->id));

        $response->assertOk();
        $response->assertSee('A101');
    }

    public function test_khong_dang_nhap_bi_chan(): void
    {
        $roomType = RoomType::factory()->create();
        $room = Room::create(['room_type_id' => $roomType->id, 'room_number' => 'A101', 'housekeeping_status' => 'clean']);

        $response = $this->get(route('staff.rooms.show', $room->id));

        $response->assertRedirect(route('staff.login'));
    }
}
