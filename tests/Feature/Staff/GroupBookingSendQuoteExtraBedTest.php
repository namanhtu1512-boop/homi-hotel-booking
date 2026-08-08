<?php

namespace Tests\Feature\Staff;

use App\Models\ChatMessage;
use App\Models\GroupBookingRequest;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupBookingSendQuoteExtraBedTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsStaff(): User
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff);
        session(['login_context' => 'staff']);

        return $staff;
    }

    public function test_gui_bao_gia_khong_co_extra_beds_khong_them_dong_phu_thu(): void
    {
        $this->loginAsStaff();
        $customer     = User::factory()->create(['role' => 'customer']);
        $roomType     = RoomType::factory()->create(['price_per_night' => 900000]);
        $groupRequest = GroupBookingRequest::factory()->create([
            'user_id'      => $customer->id,
            'num_children' => 0,
        ]);

        $response = $this->post(route('staff.group-bookings.send-quote', $groupRequest->id), [
            'quote_items' => [
                ['room_type_id' => $roomType->id, 'quantity' => 5, 'price_per_night' => 900000],
            ],
        ]);

        $response->assertRedirect(route('staff.group-bookings.show', $groupRequest->id));

        $message = ChatMessage::where('customer_id', $customer->id)->latest()->first();
        $this->assertNotNull($message);
        $this->assertStringNotContainsString('Giường phụ', $message->body);
    }

    public function test_gui_bao_gia_co_extra_beds_them_dung_dong_phu_thu_va_tong(): void
    {
        $this->loginAsStaff();
        $customer     = User::factory()->create(['role' => 'customer']);
        $roomType     = RoomType::factory()->create(['price_per_night' => 900000]);
        $groupRequest = GroupBookingRequest::factory()->create([
            'user_id'      => $customer->id,
            'num_children' => 3,
            'check_in'     => now()->addDays(10)->toDateString(),
            'check_out'    => now()->addDays(12)->toDateString(),
        ]);

        $response = $this->post(route('staff.group-bookings.send-quote', $groupRequest->id), [
            'quote_items' => [
                ['room_type_id' => $roomType->id, 'quantity' => 5, 'price_per_night' => 900000],
            ],
            'extra_beds'                => 3,
            'extra_bed_price_per_night' => 200000,
        ]);

        $response->assertRedirect(route('staff.group-bookings.show', $groupRequest->id));

        $message = ChatMessage::where('customer_id', $customer->id)->latest()->first();
        $this->assertNotNull($message);
        $this->assertStringContainsString('Giường phụ trẻ em (6-11 tuổi): 3 giường', $message->body);

        // Phòng: 5 × 900.000đ × 2 đêm = 9.000.000đ
        // Giường phụ: 3 × 200.000đ × 2 đêm = 1.200.000đ
        // Tổng: 10.200.000đ
        $this->assertStringContainsString('1.200.000đ', $message->body);
        $this->assertStringContainsString('10.200.000đ', $message->body);
    }

    public function test_gui_bao_gia_extra_beds_bang_0_khong_them_dong(): void
    {
        $this->loginAsStaff();
        $customer     = User::factory()->create(['role' => 'customer']);
        $roomType     = RoomType::factory()->create(['price_per_night' => 900000]);
        $groupRequest = GroupBookingRequest::factory()->create(['user_id' => $customer->id]);

        $this->post(route('staff.group-bookings.send-quote', $groupRequest->id), [
            'quote_items' => [
                ['room_type_id' => $roomType->id, 'quantity' => 5, 'price_per_night' => 900000],
            ],
            'extra_beds'                => 0,
            'extra_bed_price_per_night' => 200000,
        ]);

        $message = ChatMessage::where('customer_id', $customer->id)->latest()->first();
        $this->assertNotNull($message);
        $this->assertStringNotContainsString('Giường phụ', $message->body);
    }

    public function test_trang_show_hien_khoi_giuong_phu_khi_co_num_children(): void
    {
        $this->loginAsStaff();
        $groupRequest = GroupBookingRequest::factory()->create(['num_children' => 3]);

        $response = $this->get(route('staff.group-bookings.show', $groupRequest->id));

        $response->assertOk();
        $response->assertSee('Giường phụ trẻ em (6-11 tuổi)');
        $response->assertSee('id="extra_beds"', false);
    }

    public function test_trang_show_an_khoi_giuong_phu_khi_khong_co_num_children(): void
    {
        $this->loginAsStaff();
        $groupRequest = GroupBookingRequest::factory()->create(['num_children' => 0]);

        $response = $this->get(route('staff.group-bookings.show', $groupRequest->id));

        $response->assertOk();
        $response->assertDontSee('id="extra_beds"', false);
    }
}
