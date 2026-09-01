<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\GroupBookingRequest;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupBookingCreateBookingGuestCountTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        session(['login_context' => 'admin']);

        return $admin;
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
        $this->loginAsAdmin();
        $roomType     = RoomType::factory()->create(['category' => 'standard', 'capacity' => 2, 'total_rooms' => 20]);
        $groupRequest = GroupBookingRequest::factory()->create(['group_size' => 25, 'num_children' => 0]);

        // Chỉ nhập 10 khách (5 phòng x 2 NL) trong khi yêu cầu ghi 25 khách.
        $response = $this->post(route('admin.group-bookings.create-booking', $groupRequest->id), $this->baseFormData($roomType));

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame(1, Booking::count());
        $this->assertSame('converted', $groupRequest->fresh()->status);
    }

    public function test_nhap_dung_so_khach_va_tre_em_thi_tao_don_thanh_cong(): void
    {
        $this->loginAsAdmin();
        // Family: giường phụ cho trẻ em không phụ thuộc sức chứa người lớn
        // (xem BookingService::extraBedsNeeded) — cho phép test tập trung
        // vào việc đối chiếu tổng số khách mà không vướng lỗi sức chứa khác.
        $roomType     = RoomType::factory()->create(['category' => 'family', 'capacity' => 4, 'total_rooms' => 20]);
        $groupRequest = GroupBookingRequest::factory()->create(['group_size' => 25, 'num_children' => 12]);

        $response = $this->post(route('admin.group-bookings.create-booking', $groupRequest->id), $this->baseFormData($roomType, [
            'quantity'   => 7,
            'adults'     => 13,
            'children'   => 12,
            'extra_bed'  => 1,
        ]));

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame(1, Booking::count());
        $this->assertSame('converted', $groupRequest->fresh()->status);
    }

    public function test_du_tre_so_sinh_duoc_luu_dung_vao_booking(): void
    {
        $this->loginAsAdmin();
        $roomType     = RoomType::factory()->create(['category' => 'family', 'capacity' => 4, 'total_rooms' => 20]);
        $groupRequest = GroupBookingRequest::factory()->create(['group_size' => 8, 'num_children' => 0, 'num_infants' => 2]);

        $response = $this->post(route('admin.group-bookings.create-booking', $groupRequest->id), $this->baseFormData($roomType, [
            'quantity' => 2,
            'adults'   => 8,
            'infants'  => 2,
        ]));

        $response->assertSessionDoesntHaveErrors();
        $booking = Booking::first();
        $this->assertNotNull($booking);
        // items.*.infants trước đây thiếu rule validate nên bị Laravel
        // validated() tự loại bỏ — đơn tạo ra luôn ghi infants=0 dù nhập gì.
        $this->assertSame(2, $booking->infants);
    }
}
