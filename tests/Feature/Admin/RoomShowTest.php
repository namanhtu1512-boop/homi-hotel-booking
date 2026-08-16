<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomShowTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);
        session(['login_context' => 'admin']);

        return $admin;
    }

    public function test_xem_chi_tiet_phong_kem_lich_su_thay_doi(): void
    {
        $admin = $this->loginAsAdmin();
        $roomType = RoomType::factory()->create();
        $room = Room::create(['room_type_id' => $roomType->id, 'room_number' => 'A101', 'housekeeping_status' => 'clean']);

        AuditLog::create([
            'user_id'        => $admin->id,
            'action'         => 'room.updated',
            'auditable_type' => $room->getMorphClass(),
            'auditable_id'   => $room->id,
            'description'    => 'Cập nhật phòng "A101".',
        ]);

        $response = $this->get(route('admin.rooms.show', $room->id));

        $response->assertOk();
        $response->assertSee('A101');
        $response->assertSee('Cập nhật phòng &quot;A101&quot;.', false);
    }

    public function test_khong_dang_nhap_bi_chan(): void
    {
        $roomType = RoomType::factory()->create();
        $room = Room::create(['room_type_id' => $roomType->id, 'room_number' => 'A101', 'housekeeping_status' => 'clean']);

        $response = $this->get(route('admin.rooms.show', $room->id));

        $response->assertRedirect(route('admin.login'));
    }
}
