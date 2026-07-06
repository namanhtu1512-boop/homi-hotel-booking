<?php

namespace Tests\Feature\Booking;

use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hóa đơn/biên nhận nội bộ (không phải hóa đơn điện tử thật) — xem
 * resources/views/bookings/invoice.blade.php, dùng chung cho
 * customer/admin/staff.
 */
class InvoiceViewTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(): array
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);

        $this->actingAs($customer)->post('/customer/bookings', [
            'items'          => [['room_type_id' => $roomType->id, 'quantity' => 1, 'adults' => 1, 'children' => 0]],
            'check_in'       => now()->addDays(5)->format('Y-m-d'),
            'check_out'      => now()->addDays(7)->format('Y-m-d'),
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
        ]);

        return [$customer, $customer->bookings()->first()];
    }

    public function test_customer_can_view_own_invoice(): void
    {
        [$customer, $booking] = $this->makeBooking();

        $response = $this->actingAs($customer)->get("/customer/bookings/{$booking->id}/invoice");

        $response->assertOk();
        $response->assertSee($booking->booking_code);
        $response->assertSee('INV-' . $booking->booking_code);
        $response->assertSee(number_format($booking->total_amount, 0, ',', '.') . 'đ');
    }

    public function test_customer_cannot_view_another_customers_invoice(): void
    {
        [, $booking] = $this->makeBooking();
        $intruder = User::factory()->customer()->create();

        $this->actingAs($intruder)
            ->get("/customer/bookings/{$booking->id}/invoice")
            ->assertForbidden();
    }

    public function test_admin_can_view_any_invoice(): void
    {
        [, $booking] = $this->makeBooking();
        $admin = User::factory()->admin()->create();

        $this->actingAsAdmin($admin)
            ->get("/admin/bookings/{$booking->id}/invoice")
            ->assertOk()
            ->assertSee($booking->booking_code);
    }

    public function test_staff_can_view_any_invoice(): void
    {
        [, $booking] = $this->makeBooking();
        $staff = User::factory()->staff()->create();

        $this->actingAsAdmin($staff)
            ->get("/staff/bookings/{$booking->id}/invoice")
            ->assertOk()
            ->assertSee($booking->booking_code);
    }
}
