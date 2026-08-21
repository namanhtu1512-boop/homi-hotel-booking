<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTypeDeleteRestoreTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);
        session(['login_context' => 'admin']);

        return $admin;
    }

    public function test_xoa_loai_phong_khong_co_booking_active_thi_xoa_mem(): void
    {
        $this->loginAsAdmin();
        $roomType = RoomType::factory()->create();

        $response = $this->delete(route('admin.room-types.destroy', $roomType->id));

        $response->assertRedirect(route('admin.room-types.index'));
        $this->assertSoftDeleted('room_types', ['id' => $roomType->id]);
    }

    public function test_xoa_loai_phong_co_booking_active_thi_chuyen_sang_an_khong_xoa_mem(): void
    {
        $this->loginAsAdmin();
        $roomType = RoomType::factory()->create(['status' => 'active']);

        $booking = Booking::factory()->create(['status' => BookingStatus::CONFIRMED]);
        BookingItem::factory()->create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
        ]);

        $response = $this->delete(route('admin.room-types.destroy', $roomType->id));

        $response->assertRedirect(route('admin.room-types.index'));
        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'status' => 'hidden', 'deleted_at' => null]);
    }

    public function test_khoi_phuc_loai_phong_da_xoa(): void
    {
        $this->loginAsAdmin();
        $roomType = RoomType::factory()->create();
        $roomType->delete();

        $response = $this->post(route('admin.room-types.restore', $roomType->id));

        $response->assertRedirect(route('admin.room-types.index'));
        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'deleted_at' => null]);
    }

    public function test_khoi_phuc_that_bai_neu_id_khong_o_trang_thai_da_xoa(): void
    {
        $this->loginAsAdmin();
        $roomType = RoomType::factory()->create();

        $response = $this->post(route('admin.room-types.restore', $roomType->id));

        $response->assertNotFound();
    }

    public function test_trang_danh_sach_hien_thi_khu_vuc_da_xoa_va_nut_khoi_phuc(): void
    {
        $this->loginAsAdmin();
        $roomType = RoomType::factory()->create(['name' => 'Phòng Deluxe Test']);
        $roomType->delete();

        $response = $this->get(route('admin.room-types.index'));

        $response->assertOk();
        $response->assertSee('Phòng Deluxe Test');
        $response->assertSee('Khôi phục');
        $response->assertSee(route('admin.room-types.restore', $roomType->id), false);
    }

    public function test_khong_co_loai_phong_da_xoa_thi_khong_hien_khu_vuc_da_xoa(): void
    {
        $this->loginAsAdmin();
        RoomType::factory()->create();

        $response = $this->get(route('admin.room-types.index'));

        $response->assertOk();
        $response->assertDontSee('loại phòng đã xóa mềm');
    }

    public function test_loai_phong_da_xoa_khong_hien_trong_danh_sach_dang_hoat_dong(): void
    {
        $this->loginAsAdmin();
        $active = RoomType::factory()->create(['name' => 'Phòng Còn Hoạt Động']);
        $trashed = RoomType::factory()->create(['name' => 'Phòng Đã Xóa']);
        $trashed->delete();

        $response = $this->get(route('admin.room-types.index'));

        $response->assertOk();
        // "Phòng Đã Xóa" chỉ xuất hiện đúng 1 lần (trong khu vực "Đã xóa"),
        // không lẫn vào bảng danh sách đang hoạt động ở trên.
        $response->assertSeeInOrder(['Phòng Còn Hoạt Động', 'Đã xóa', 'Phòng Đã Xóa']);
    }
}
