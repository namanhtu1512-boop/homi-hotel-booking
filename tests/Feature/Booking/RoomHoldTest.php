<?php

namespace Tests\Feature\Booking;

use App\Models\RoomHold;
use App\Models\RoomType;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\DateRangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Giữ chỗ tạm thời (10-15 phút) khi khách đang điền form đặt phòng — xem
 * RoomHoldService/AvailabilityService.
 */
class RoomHoldTest extends TestCase
{
    use RefreshDatabase;

    private function d(int $offsetDays): string
    {
        return now()->addDays(30 + $offsetDays)->toDateString();
    }

    public function test_checking_availability_creates_a_room_hold_for_the_session(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);

        $this->actingAs($customer)->get('/customer/bookings/create?' . http_build_query([
            'items'     => [['room_type_id' => $roomType->id, 'quantity' => 1]],
            'check_in'  => $this->d(5),
            'check_out' => $this->d(7),
        ]))->assertOk();

        $hold = RoomHold::where('room_type_id', $roomType->id)->first();

        $this->assertNotNull($hold);
        $this->assertSame($this->d(5), $hold->check_in->toDateString());
        $this->assertSame($this->d(7), $hold->check_out->toDateString());
        $this->assertTrue($hold->expires_at->isFuture());
        $this->assertTrue($hold->expires_at->lessThanOrEqualTo(now()->addMinutes(15)->addSecond()));
    }

    public function test_active_room_hold_from_another_session_reduces_availability(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 3]);

        RoomHold::factory()->create([
            'room_type_id' => $roomType->id,
            'session_id'   => 'other-session',
            'check_in'     => $this->d(5),
            'check_out'    => $this->d(7),
            'quantity'     => 2,
        ]);

        $service = new AvailabilityService(new DateRangeService());
        $result  = $service->check($roomType->id, $this->d(5), $this->d(7), 1, 'my-session');

        $this->assertSame(1, $result['available_quantity']);
    }

    public function test_own_session_hold_does_not_block_own_availability_check(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 3]);

        RoomHold::factory()->create([
            'room_type_id' => $roomType->id,
            'session_id'   => 'my-session',
            'check_in'     => $this->d(5),
            'check_out'    => $this->d(7),
            'quantity'     => 2,
        ]);

        $service = new AvailabilityService(new DateRangeService());
        $result  = $service->check($roomType->id, $this->d(5), $this->d(7), 1, 'my-session');

        $this->assertSame(3, $result['available_quantity']);
    }

    public function test_expired_room_hold_does_not_reduce_availability(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 3]);

        RoomHold::factory()->expired()->create([
            'room_type_id' => $roomType->id,
            'session_id'   => 'other-session',
            'check_in'     => $this->d(5),
            'check_out'    => $this->d(7),
            'quantity'     => 2,
        ]);

        $service = new AvailabilityService(new DateRangeService());
        $result  = $service->check($roomType->id, $this->d(5), $this->d(7), 1, 'my-session');

        $this->assertSame(3, $result['available_quantity']);
    }

    public function test_completing_a_booking_releases_the_customers_room_hold(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['total_rooms' => 3, 'price_per_night' => 1000000]);

        $query = http_build_query([
            'items'     => [['room_type_id' => $roomType->id, 'quantity' => 1]],
            'check_in'  => $this->d(5),
            'check_out' => $this->d(7),
        ]);

        // Bước 1: khách "Kiểm tra phòng trống" — tạo hold cho session hiện tại.
        $this->actingAs($customer)->get("/customer/bookings/create?{$query}")->assertOk();
        $this->assertSame(1, RoomHold::where('room_type_id', $roomType->id)->count());

        // HTTP test client không tự mang cookie session giữa các lần gọi
        // riêng biệt — phải lấy đúng session id vừa dùng và gắn lại vào
        // cookie cho request kế tiếp để mô phỏng đúng "cùng một trình
        // duyệt" (nếu không, request 2 sẽ có session id ngẫu nhiên khác).
        $sessionId = $this->app['session']->getId();
        $this->withCookie(config('session.cookie'), $sessionId);

        // Bước 2: khách hoàn tất đặt phòng — hold của chính session này phải
        // được giải phóng, và không hề chặn việc tạo booking thật của nó.
        $response = $this->actingAs($customer)->post('/customer/bookings', [
            'items'          => [['room_type_id' => $roomType->id, 'quantity' => 1, 'adults' => 1, 'children' => 0]],
            'check_in'       => $this->d(5),
            'check_out'      => $this->d(7),
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
        ]);

        $booking = $customer->bookings()->first();
        $response->assertRedirect(route('customer.bookings.show', $booking->id));
        $this->assertSame(0, RoomHold::count());
    }

    public function test_cleanup_command_deletes_only_expired_holds(): void
    {
        $roomType = RoomType::factory()->create();

        $active  = RoomHold::factory()->create(['room_type_id' => $roomType->id]);
        $expired = RoomHold::factory()->expired()->create(['room_type_id' => $roomType->id]);

        $this->artisan('room-holds:cleanup')->assertExitCode(0);

        $this->assertNotNull($active->fresh());
        $this->assertNull($expired->fresh());
    }
}
