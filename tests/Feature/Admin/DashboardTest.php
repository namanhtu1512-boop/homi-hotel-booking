<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\RoomType;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE4 - Tuần 13: Dashboard thống kê — đối chiếu số liệu DashboardService trả
 * về với dữ liệu thật trong DB (không chỉ kiểm tra quyền truy cập route).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(string $status, RoomType $roomType, int $quantity = 1): Booking
    {
        $booking = Booking::create([
            'booking_code'   => 'TEST-' . uniqid(),
            'check_in'       => now()->addDays(5),
            'check_out'      => now()->addDays(7),
            'nights'         => 2,
            'customer_name'  => 'Khách Test',
            'customer_phone' => '0900000000',
            'total_amount'   => $roomType->price_per_night * 2 * $quantity,
            'status'         => $status,
        ]);

        BookingItem::create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomType->id,
            'quantity'        => $quantity,
            'price_per_night' => $roomType->price_per_night,
            'nights'          => 2,
            'subtotal'        => $roomType->price_per_night * 2 * $quantity,
        ]);

        return $booking;
    }

    public function test_stats_match_actual_database_counts(): void
    {
        User::factory()->customer()->count(3)->create();
        $roomType = RoomType::factory()->create(['status' => 'active']);

        $this->makeBooking('pending', $roomType);
        $this->makeBooking('pending', $roomType);
        $this->makeBooking('confirmed', $roomType);
        $this->makeBooking('cancelled', $roomType);
        $this->makeBooking('cancelled', $roomType);

        $stats = app(DashboardService::class)->stats();

        $this->assertSame(3, $stats['total_customers']);
        $this->assertSame(5, $stats['total_bookings']);
        $this->assertSame(2, $stats['pending_bookings']);
        $this->assertSame(1, $stats['confirmed_bookings']);
        $this->assertSame(2, $stats['cancelled_bookings']);
        // 2 hủy / 5 tổng = 40%
        $this->assertSame(40, $stats['cancellation_rate']);
    }

    public function test_cancellation_rate_is_zero_when_no_bookings_exist(): void
    {
        $stats = app(DashboardService::class)->stats();

        $this->assertSame(0, $stats['total_bookings']);
        $this->assertSame(0, $stats['cancellation_rate']);
    }

    public function test_occupancy_rate_matches_rooms_held_today(): void
    {
        $roomType = RoomType::factory()->create(['status' => 'active', 'total_rooms' => 10]);

        $booking = Booking::create([
            'booking_code'   => 'TEST-' . uniqid(),
            'check_in'       => now()->subDay(),
            'check_out'      => now()->addDay(),
            'nights'         => 2,
            'customer_name'  => 'Khách Test',
            'customer_phone' => '0900000000',
            'total_amount'   => 0,
            'status'         => 'confirmed',
        ]);

        BookingItem::create([
            'booking_id'      => $booking->id,
            'room_type_id'    => $roomType->id,
            'quantity'        => 4,
            'price_per_night' => $roomType->price_per_night,
            'nights'          => 2,
            'subtotal'        => 0,
        ]);

        $occupancy = app(DashboardService::class)->occupancyRate();

        $this->assertSame(10, $occupancy['total']);
        $this->assertSame(4, $occupancy['occupied']);
        $this->assertSame(6, $occupancy['available']);
        $this->assertSame(40, $occupancy['rate']);
    }

    public function test_revenue_by_month_sums_paid_and_deposit_paid_payments_in_current_month(): void
    {
        $roomType = RoomType::factory()->create(['status' => 'active']);
        $booking  = $this->makeBooking('confirmed', $roomType);

        Payment::create([
            'booking_id' => $booking->id,
            'method'     => 'online_demo',
            'amount'     => 1000000,
            'status'     => 'paid',
            'paid_at'    => now(),
        ]);

        $booking2 = $this->makeBooking('confirmed', $roomType);

        Payment::create([
            'booking_id'       => $booking2->id,
            'method'           => 'bank_transfer',
            'amount'           => 2000000,
            'deposit_amount'   => 300000,
            'status'           => 'deposit_paid',
            'deposit_paid_at'  => now(),
        ]);

        $revenue = app(DashboardService::class)->revenueByMonth(1);

        $this->assertSame([1300000.0], $revenue['totals']);
    }

    public function test_admin_dashboard_view_receives_matching_stats(): void
    {
        $admin    = User::factory()->admin()->create();
        $roomType = RoomType::factory()->create(['status' => 'active']);
        $this->makeBooking('cancelled', $roomType);
        $this->makeBooking('confirmed', $roomType);

        $this->actingAsAdmin($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['total_bookings'] === 2 && $stats['cancellation_rate'] === 50);
    }
}
