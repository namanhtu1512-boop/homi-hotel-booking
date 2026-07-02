<?php

namespace Tests\Feature\Booking;

use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tuần 10 BE4 — E2E test: search → room detail → kiểm tra phòng trống →
 * tạo đơn → thông báo thành công kèm mã đơn.
 */
class BookingE2ETest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function makeRoomType(array $attrs = []): RoomType
    {
        return RoomType::factory()->create(array_merge([
            'total_rooms'    => 5,
            'price_per_night' => 1000000,
        ], $attrs));
    }

    private function checkIn(): string
    {
        return now()->addDays(3)->format('Y-m-d');
    }

    private function checkOut(): string
    {
        return now()->addDays(5)->format('Y-m-d');
    }

    // ----------------------------------------------------------------
    // Bước 1 — Public: tìm phòng (search / list)
    // ----------------------------------------------------------------

    public function test_step1_public_can_list_active_rooms(): void
    {
        $active = $this->makeRoomType(['status' => 'active']);
        RoomType::factory()->hidden()->create();

        $this->get('/rooms')
            ->assertOk()
            ->assertSee($active->name);
    }

    public function test_step1_search_by_keyword_filters_results(): void
    {
        $target  = $this->makeRoomType(['name' => 'Phòng Deluxe Đặc Biệt']);
        $other   = $this->makeRoomType(['name' => 'Suite Thường']);

        $this->get('/rooms?keyword=Deluxe')
            ->assertOk()
            ->assertSee($target->name)
            ->assertDontSee($other->name);
    }

    // ----------------------------------------------------------------
    // Bước 2 — Room detail với form availability
    // ----------------------------------------------------------------

    public function test_step2_room_detail_page_is_accessible(): void
    {
        $roomType = $this->makeRoomType();

        $this->get("/rooms/{$roomType->id}")
            ->assertOk()
            ->assertSee($roomType->name);
    }

    public function test_step2_room_detail_shows_availability_result(): void
    {
        $roomType = $this->makeRoomType(['total_rooms' => 3]);

        $this->get("/rooms/{$roomType->id}?check_in={$this->checkIn()}&check_out={$this->checkOut()}")
            ->assertOk()
            ->assertSee('Còn 3 phòng trống');
    }

    public function test_step2_inactive_room_returns_404(): void
    {
        $hidden = RoomType::factory()->hidden()->create();

        $this->get("/rooms/{$hidden->id}")->assertNotFound();
    }

    // ----------------------------------------------------------------
    // Bước 3 — Redirect tới login nếu chưa đăng nhập
    // ----------------------------------------------------------------

    public function test_step3_unauthenticated_booking_create_redirects_to_login(): void
    {
        $this->get('/customer/bookings/create')->assertRedirect(route('login'));
    }

    public function test_step3_unauthenticated_booking_post_redirects_to_login(): void
    {
        $roomType = $this->makeRoomType();

        $this->post('/customer/bookings', [
            'room_type_id'   => $roomType->id,
            'check_in'       => $this->checkIn(),
            'check_out'      => $this->checkOut(),
            'quantity'       => 1,
            'customer_name'  => 'Khách',
            'customer_phone' => '0900000001',
        ])->assertRedirect(route('login'));
    }

    // ----------------------------------------------------------------
    // Bước 4 — Customer đặt phòng thành công
    // ----------------------------------------------------------------

    public function test_step4_customer_creates_booking_and_sees_success_message(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = $this->makeRoomType(['price_per_night' => 1000000]);

        $response = $this->actingAs($customer)->post('/customer/bookings', [
            'room_type_id'   => $roomType->id,
            'check_in'       => $this->checkIn(),
            'check_out'      => $this->checkOut(),
            'quantity'       => 1,
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
            'customer_email' => 'a@example.com',
        ]);

        $booking = $customer->bookings()->first();

        // Redirect đúng trang chi tiết đơn
        $response->assertRedirect(route('customer.bookings.show', $booking->id));

        // Flash message chứa mã đơn
        $response->assertSessionHas('success');
        $this->assertStringContainsString($booking->booking_code, session('success'));

        // Mã đơn đúng format HOMI-YYYYMMDD-XXXXXX
        $this->assertMatchesRegularExpression('/^HOMI-\d{8}-[A-Z0-9]{6}$/', $booking->booking_code);
    }

    public function test_step4_booking_calculates_price_correctly(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = $this->makeRoomType(['price_per_night' => 500000]);

        $this->actingAs($customer)->post('/customer/bookings', [
            'room_type_id'   => $roomType->id,
            'check_in'       => $this->checkIn(),   // +3 ngày
            'check_out'      => $this->checkOut(),  // +5 ngày → 2 đêm
            'quantity'       => 2,
            'customer_name'  => 'Test',
            'customer_phone' => '0900000000',
        ]);

        $booking = $customer->bookings()->first();

        $this->assertEquals(2, $booking->nights);
        $this->assertEquals(2000000, $booking->total_amount); // 500k × 2 đêm × 2 phòng
    }

    public function test_step4_booking_creates_payment_pending_record(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = $this->makeRoomType();

        $this->actingAs($customer)->post('/customer/bookings', [
            'room_type_id'   => $roomType->id,
            'check_in'       => $this->checkIn(),
            'check_out'      => $this->checkOut(),
            'quantity'       => 1,
            'customer_name'  => 'Test',
            'customer_phone' => '0900000000',
        ]);

        $booking = $customer->bookings()->first();

        $this->assertNotNull($booking->payment);
        $this->assertEquals('unpaid', $booking->payment->status->value);
        $this->assertEquals($booking->total_amount, $booking->payment->amount);
    }

    // ----------------------------------------------------------------
    // Bước 5 — Không đặt được khi hết phòng
    // ----------------------------------------------------------------

    public function test_step5_cannot_book_when_room_is_full(): void
    {
        $customer1 = User::factory()->customer()->create();
        $customer2 = User::factory()->customer()->create();
        $roomType  = $this->makeRoomType(['total_rooms' => 1]);

        $payload = [
            'room_type_id'   => $roomType->id,
            'check_in'       => $this->checkIn(),
            'check_out'      => $this->checkOut(),
            'quantity'       => 1,
            'customer_name'  => 'Khách',
            'customer_phone' => '0900000001',
        ];

        // Customer 1 đặt thành công
        $this->actingAs($customer1)->post('/customer/bookings', $payload)->assertRedirect();

        // Customer 2 đặt cùng ngày → hết phòng
        $this->actingAs($customer2)
            ->post('/customer/bookings', $payload)
            ->assertSessionHasErrors('room_type_id');

        $this->assertCount(0, $customer2->bookings);
    }

    // ----------------------------------------------------------------
    // Bước 6 — Customer xem đơn thành công với mã đơn
    // ----------------------------------------------------------------

    public function test_step6_customer_can_view_booking_detail_with_booking_code(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = $this->makeRoomType();

        $this->actingAs($customer)->post('/customer/bookings', [
            'room_type_id'   => $roomType->id,
            'check_in'       => $this->checkIn(),
            'check_out'      => $this->checkOut(),
            'quantity'       => 1,
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
        ]);

        $booking = $customer->bookings()->first();

        $this->actingAs($customer)
            ->get("/customer/bookings/{$booking->id}")
            ->assertOk()
            ->assertSee($booking->booking_code)
            ->assertSee('Chờ xác nhận');
    }
}
