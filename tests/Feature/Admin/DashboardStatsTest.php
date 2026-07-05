<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test dashboard thống kê cơ bản (US10) — số liệu phải khớp DB.
 *
 * Test case ID | Chức năng                                  | Kết quả mong đợi
 * TC-DSH-001   | Admin xem trang dashboard                   | 200
 * TC-DSH-002   | Staff xem trang dashboard                   | 200
 * TC-DSH-003   | Tổng đơn/pending/confirmed/cancelled khớp DB| Đúng số đếm thực tế
 * TC-DSH-004   | Tổng số khách hàng khớp DB                  | Đúng số user role=customer
 * TC-DSH-005   | Tỷ lệ hủy tính đúng theo % đơn cancelled     | Đúng công thức cancelled/total
 * TC-DSH-006   | Tỷ lệ hủy = 0 khi chưa có đơn nào            | Không chia cho 0
 */
class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function makeBooking(string $status): Booking
    {
        $customer = User::factory()->customer()->create();

        return Booking::create([
            'booking_code'   => 'DASH-' . uniqid(),
            'user_id'        => $customer->id,
            'check_in'       => now()->addDays(5)->format('Y-m-d'),
            'check_out'      => now()->addDays(7)->format('Y-m-d'),
            'nights'         => 2,
            'customer_name'  => $customer->name,
            'customer_phone' => '0900000000',
            'total_amount'   => 2000000,
            'status'         => $status,
        ]);
    }

    public function test_admin_can_view_dashboard(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)->get('/admin/dashboard')->assertOk();
    }

    public function test_staff_can_view_dashboard(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAsAdmin($staff)->get('/staff/dashboard')->assertOk();
    }

    public function test_booking_counts_match_database(): void
    {
        $this->makeBooking('pending');
        $this->makeBooking('pending');
        $this->makeBooking('confirmed');
        $this->makeBooking('cancelled');

        $stats = app(DashboardService::class)->stats();

        $this->assertSame(4, $stats['total_bookings']);
        $this->assertSame(2, $stats['pending_bookings']);
        $this->assertSame(1, $stats['confirmed_bookings']);
        $this->assertSame(1, $stats['cancelled_bookings']);
        $this->assertSame(Booking::count(), $stats['total_bookings']);
    }

    public function test_total_customers_matches_database(): void
    {
        User::factory()->customer()->count(3)->create();
        User::factory()->staff()->create();
        User::factory()->admin()->create();

        $stats = app(DashboardService::class)->stats();

        $this->assertSame(3, $stats['total_customers']);
        $this->assertSame(User::where('role', 'customer')->count(), $stats['total_customers']);
    }

    public function test_cancellation_rate_is_computed_correctly(): void
    {
        $this->makeBooking('cancelled');
        $this->makeBooking('cancelled');
        $this->makeBooking('confirmed');
        $this->makeBooking('completed');

        $stats = app(DashboardService::class)->stats();

        // 2 cancelled / 4 total = 50%
        $this->assertSame(50, $stats['cancellation_rate']);
    }

    public function test_cancellation_rate_is_zero_when_no_bookings_exist(): void
    {
        $stats = app(DashboardService::class)->stats();

        $this->assertSame(0, $stats['total_bookings']);
        $this->assertSame(0, $stats['cancellation_rate']);
    }

    public function test_dashboard_page_displays_cancellation_rate(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeBooking('cancelled');
        $this->makeBooking('confirmed');

        $response = $this->actingAsAdmin($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Tỷ lệ hủy đơn');
        $response->assertSee('50%');
    }
}
