<?php

namespace Tests\Feature\Staff;

use App\Models\Booking;
use App\Models\GroupBookingRequest;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupBookingCreateBookingGuestCountTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsStaff(): User
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff);
        session(['login_context' => 'staff']);

        return $staff;
    }

    private function baseFormData(RoomType $roomType, array $itemOverrides = []): array
    {
        return [
            'check_in'       => now()->addDays(10)->toDateString(),
            'check_out'      => now()->addDays(12)->toDateString(),
            'customer_name'  => 'Nam Anh Tú',
            'customer_phone' => '0900000001',
            'customer_email' => 'namanhtu1512@gmail.com',
            'items' => [array_merge([
                'room_type_id' => $roomType->id,
                'quantity'     => 5,
                'adults'       => 10,
                'children'     => 0,
                'infants'      => 0,
            ], $itemOverrides)],
        ];
    }

    public function test_so_khach_nhap_khong_khop_group_size_van_tao_don_duoc(): void
    {
        // Không còn chặn tạo đơn nếu NL/TE/SS nhập không khớp group_size/
        // num_children đã khai báo trong yêu cầu gốc — khách có thể đổi ý
        // sau khi gửi yêu cầu (qua chat/điện thoại), admin/staff cần tự do
        // điều chỉnh số liệu thật lúc tạo đơn mà không bị form chặn lại.
        $this->loginAsStaff();
        $roomType     = RoomType::factory()->create(['category' => 'standard', 'capacity' => 2, 'total_rooms' => 20]);
        $groupRequest = GroupBookingRequest::factory()->create(['group_size' => 25, 'num_children' => 0]);

        $response = $this->post(route('staff.group-bookings.create-booking', $groupRequest->id), $this->baseFormData($roomType));

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame(1, Booking::count());
        $this->assertSame('converted', $groupRequest->fresh()->status);
    }

    public function test_nhap_dung_so_khach_va_tre_em_thi_tao_don_thanh_cong(): void
    {
        $this->loginAsStaff();
        $roomType     = RoomType::factory()->create(['category' => 'family', 'capacity' => 4, 'total_rooms' => 20]);
        $groupRequest = GroupBookingRequest::factory()->create(['group_size' => 25, 'num_children' => 12]);

        $response = $this->post(route('staff.group-bookings.create-booking', $groupRequest->id), $this->baseFormData($roomType, [
            'quantity' => 7,
            'adults'   => 13,
            'children' => 12,
        ]));

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame(1, Booking::count());
        $this->assertSame('converted', $groupRequest->fresh()->status);
    }
}
