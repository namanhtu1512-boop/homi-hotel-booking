<?php

namespace Tests\Feature\Booking;

use App\Models\Promotion;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Khuyến mãi cho phép stack nhiều mã/đơn (nếu tất cả mã đều stackable) —
 * xem PromotionService::findValidManyByCodes(), BookingService::create().
 */
class PromotionStackingTest extends TestCase
{
    use RefreshDatabase;

    private function bookingPayload(RoomType $roomType, array $overrides = []): array
    {
        return array_merge([
            'items'          => [['room_type_id' => $roomType->id, 'quantity' => 1, 'adults' => 1, 'children' => 0]],
            'check_in'       => now()->addDays(5)->format('Y-m-d'),
            'check_out'      => now()->addDays(6)->format('Y-m-d'),
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
        ], $overrides);
    }

    public function test_single_non_stackable_code_still_works(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $promo    = Promotion::factory()->create(['discount_percent' => 10, 'stackable' => false]);

        $response = $this->actingAs($customer)->post('/customer/bookings', $this->bookingPayload($roomType, [
            'promo_codes' => [$promo->code],
        ]));

        $booking = $customer->bookings()->first();
        $response->assertRedirect(route('customer.bookings.show', $booking->id));
        $this->assertEquals(100000, $booking->discount_amount);
        $this->assertEquals(900000, $booking->total_amount);
        $this->assertCount(1, $booking->promotions);
    }

    public function test_two_stackable_codes_apply_sequentially_and_both_get_recorded(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $promoA   = Promotion::factory()->create(['discount_percent' => 10, 'stackable' => true]);
        $promoB   = Promotion::factory()->create(['discount_percent' => 20, 'stackable' => true]);

        $response = $this->actingAs($customer)->post('/customer/bookings', $this->bookingPayload($roomType, [
            'promo_codes' => [$promoA->code, $promoB->code],
        ]));

        $booking = $customer->bookings()->first();
        $response->assertRedirect(route('customer.bookings.show', $booking->id));

        // Mã A: 10% của 1.000.000 = 100.000, còn lại 900.000.
        // Mã B: 20% của 900.000 (phần còn lại) = 180.000.
        // Tổng giảm = 280.000, total_amount = 720.000.
        $this->assertEquals(280000, $booking->discount_amount);
        $this->assertEquals(720000, $booking->total_amount);
        $this->assertCount(2, $booking->promotions);

        $pivotA = $booking->promotions->firstWhere('id', $promoA->id)->pivot;
        $pivotB = $booking->promotions->firstWhere('id', $promoB->id)->pivot;
        $this->assertEquals(100000, $pivotA->discount_amount);
        $this->assertEquals(180000, $pivotB->discount_amount);
    }

    public function test_discount_never_exceeds_total_amount_when_stacking_large_percentages(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $promoA   = Promotion::factory()->create(['discount_percent' => 80, 'stackable' => true]);
        $promoB   = Promotion::factory()->create(['discount_percent' => 80, 'stackable' => true]);

        $this->actingAs($customer)->post('/customer/bookings', $this->bookingPayload($roomType, [
            'promo_codes' => [$promoA->code, $promoB->code],
        ]));

        $booking = $customer->bookings()->first();

        // Giảm tuần tự trên phần còn lại (không phải cộng % trực tiếp):
        // mã A giảm 80% của 1.000.000 = 800.000, còn lại 200.000;
        // mã B giảm 80% của 200.000 = 160.000. Tổng giảm 960.000, không âm.
        $this->assertEquals(960000, $booking->discount_amount);
        $this->assertEquals(40000, $booking->total_amount);
    }

    public function test_mixing_stackable_and_non_stackable_codes_is_rejected(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $stackable    = Promotion::factory()->create(['stackable' => true]);
        $nonStackable = Promotion::factory()->create(['stackable' => false]);

        $response = $this->actingAs($customer)->post('/customer/bookings', $this->bookingPayload($roomType, [
            'promo_codes' => [$stackable->code, $nonStackable->code],
        ]));

        $response->assertSessionHasErrors('promo_codes');
        $this->assertCount(0, $customer->bookings);
    }

    public function test_duplicate_code_in_same_submission_is_rejected(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        $promo    = Promotion::factory()->create(['stackable' => true]);

        $response = $this->actingAs($customer)->post('/customer/bookings', $this->bookingPayload($roomType, [
            'promo_codes' => [$promo->code, $promo->code],
        ]));

        $response->assertSessionHasErrors('promo_codes.0');
        $this->assertCount(0, $customer->bookings);
    }

    public function test_invalid_code_among_multiple_rejects_the_whole_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create();
        $valid    = Promotion::factory()->create(['stackable' => true]);

        $response = $this->actingAs($customer)->post('/customer/bookings', $this->bookingPayload($roomType, [
            'promo_codes' => [$valid->code, 'KHONGTONTAI'],
        ]));

        $response->assertSessionHasErrors('promo_codes');
        $this->assertCount(0, $customer->bookings);
    }

    public function test_comma_separated_promo_codes_text_field_is_split_into_array(): void
    {
        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $promoA   = Promotion::factory()->create(['discount_percent' => 10, 'stackable' => true]);
        $promoB   = Promotion::factory()->create(['discount_percent' => 5, 'stackable' => true]);

        $response = $this->actingAs($customer)->post('/customer/bookings', $this->bookingPayload($roomType, [
            'promo_codes_text' => "{$promoA->code}, {$promoB->code}",
        ]));

        $booking = $customer->bookings()->first();
        $response->assertRedirect(route('customer.bookings.show', $booking->id));
        $this->assertCount(2, $booking->promotions);
    }
}
