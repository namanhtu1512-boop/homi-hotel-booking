<?php

namespace Tests\Feature\Admin;

use App\Mail\GroupBookingQuoteMail;
use App\Models\ChatMessage;
use App\Models\GroupBookingRequest;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GroupBookingSendQuoteTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        session(['login_context' => 'admin']);

        return $admin;
    }

    public function test_gui_bao_gia_qua_chat_tao_dung_tin_nhan_va_tong_tien(): void
    {
        Mail::fake();

        $this->loginAsAdmin();
        $customer     = User::factory()->create(['role' => 'customer']);
        $roomType     = RoomType::factory()->create(['price_per_night' => 900000]);
        $groupRequest = GroupBookingRequest::factory()->create([
            'user_id'   => $customer->id,
            'check_in'  => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
        ]);

        $response = $this->post(route('admin.group-bookings.send-quote', $groupRequest->id), [
            'quote_items' => [
                ['room_type_id' => $roomType->id, 'quantity' => 5, 'price_per_night' => 900000],
            ],
        ]);

        $response->assertRedirect(route('admin.group-bookings.show', $groupRequest->id));

        $message = ChatMessage::where('customer_id', $customer->id)->latest()->first();
        $this->assertNotNull($message);
        // 5 phòng × 900.000đ × 2 đêm = 9.000.000đ
        $this->assertStringContainsString('9.000.000đ', $message->body);

        $this->assertSame('contacted', $groupRequest->fresh()->status);

        Mail::assertSent(GroupBookingQuoteMail::class, function (GroupBookingQuoteMail $mail) use ($groupRequest) {
            return $mail->hasTo($groupRequest->email) && (float) $mail->quote['total'] === 9000000.0;
        });
    }

    public function test_gui_bao_gia_co_extra_beds_them_dong_phu_thu(): void
    {
        $this->loginAsAdmin();
        $customer     = User::factory()->create(['role' => 'customer']);
        $roomType     = RoomType::factory()->create(['price_per_night' => 900000]);
        $groupRequest = GroupBookingRequest::factory()->create([
            'user_id'      => $customer->id,
            'num_children' => 3,
            'check_in'     => now()->addDays(10)->toDateString(),
            'check_out'    => now()->addDays(12)->toDateString(),
        ]);

        $this->post(route('admin.group-bookings.send-quote', $groupRequest->id), [
            'quote_items' => [
                ['room_type_id' => $roomType->id, 'quantity' => 5, 'price_per_night' => 900000],
            ],
            'extra_beds'                => 3,
            'extra_bed_price_per_night' => 200000,
        ]);

        $message = ChatMessage::where('customer_id', $customer->id)->latest()->first();
        $this->assertStringContainsString('Giường phụ trẻ em (6-11 tuổi): 3 giường', $message->body);
        $this->assertStringContainsString('10.200.000đ', $message->body);
    }

    public function test_khong_co_tai_khoan_lien_ket_van_gui_duoc_qua_email(): void
    {
        Mail::fake();

        $this->loginAsAdmin();
        $roomType     = RoomType::factory()->create(['price_per_night' => 900000]);
        $groupRequest = GroupBookingRequest::factory()->create(['user_id' => null]);

        $response = $this->post(route('admin.group-bookings.send-quote', $groupRequest->id), [
            'quote_items' => [
                ['room_type_id' => $roomType->id, 'quantity' => 1, 'price_per_night' => 900000],
            ],
        ]);

        $response->assertRedirect(route('admin.group-bookings.show', $groupRequest->id));
        $this->assertSame(0, ChatMessage::count());
        $this->assertSame('contacted', $groupRequest->fresh()->status);

        Mail::assertSent(GroupBookingQuoteMail::class, fn (GroupBookingQuoteMail $mail) => $mail->hasTo($groupRequest->email));
    }

    public function test_trang_show_admin_co_form_gui_bao_gia(): void
    {
        $this->loginAsAdmin();
        $groupRequest = GroupBookingRequest::factory()->create(['user_id' => User::factory()->create(['role' => 'customer'])->id]);

        $response = $this->get(route('admin.group-bookings.show', $groupRequest->id));

        $response->assertOk();
        $response->assertSee('Gửi báo giá');
        $response->assertSee(route('admin.group-bookings.send-quote', $groupRequest->id), false);
    }
}
