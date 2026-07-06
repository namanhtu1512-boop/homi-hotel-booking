<?php

namespace Tests\Feature\Booking;

use App\Models\HotelInfo;
use App\Models\RoomType;
use App\Models\SeasonalRate;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Giá theo mùa/cuối tuần + phụ thu trẻ em — xem PricingService.
 */
class PricingCalculationTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(PricingService::class);
    }

    /**
     * Thứ Năm cách xa hôm nay (an toàn cho DateRangeService::validate) —
     * Thứ Sáu/Bảy kế tiếp là 2 đêm cuối tuần.
     */
    private function thursday(): Carbon
    {
        return Carbon::now()->addDays(60)->next(Carbon::THURSDAY);
    }

    public function test_flat_rate_unchanged_without_seasonal_weekend_or_children(): void
    {
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $checkIn  = $this->thursday();
        $checkOut = $checkIn->copy()->addDays(2);

        $result = $this->service->calculate($roomType, $checkIn->toDateString(), $checkOut->toDateString(), 1);

        $this->assertEquals(2000000, $result['total_price']);
        $this->assertEquals(0, $result['child_surcharge']);
    }

    public function test_weekend_surcharge_applies_only_to_friday_and_saturday_nights(): void
    {
        HotelInfo::instance()->update(['weekend_surcharge_percent' => 10]);

        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $checkIn  = $this->thursday();      // Thu
        $checkOut = $checkIn->copy()->addDays(3); // Thu, Fri, Sat

        $result = $this->service->calculate($roomType, $checkIn->toDateString(), $checkOut->toDateString(), 1);

        // Thu: 1.000.000, Fri: 1.100.000, Sat: 1.100.000
        $this->assertEquals(3200000, $result['room_subtotal']);
        $this->assertFalse($result['nightly_breakdown'][0]['is_weekend']);
        $this->assertTrue($result['nightly_breakdown'][1]['is_weekend']);
        $this->assertTrue($result['nightly_breakdown'][2]['is_weekend']);
        $this->assertEquals(100000, $result['nightly_breakdown'][1]['weekend_surcharge']);
    }

    public function test_seasonal_rate_only_affects_nights_within_its_window(): void
    {
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $checkIn  = $this->thursday();
        $checkOut = $checkIn->copy()->addDays(3);

        // Rate chỉ phủ đúng đêm thứ 2 (Fri) trong chuỗi 3 đêm.
        SeasonalRate::factory()->create([
            'room_type_id'     => $roomType->id,
            'start_date'       => $checkIn->copy()->addDay()->toDateString(),
            'end_date'         => $checkIn->copy()->addDay()->toDateString(),
            'adjustment_type'  => 'fixed_per_night',
            'adjustment_value' => 300000,
        ]);

        $result = $this->service->calculate($roomType, $checkIn->toDateString(), $checkOut->toDateString(), 1);

        $this->assertEquals(0, $result['nightly_breakdown'][0]['seasonal_adjustment']);
        $this->assertEquals(300000, $result['nightly_breakdown'][1]['seasonal_adjustment']);
        $this->assertEquals(0, $result['nightly_breakdown'][2]['seasonal_adjustment']);
        // Thu 1.000.000 + Fri 1.300.000 + Sat 1.000.000
        $this->assertEquals(3300000, $result['room_subtotal']);
    }

    public function test_seasonal_adjustment_applies_before_weekend_surcharge(): void
    {
        HotelInfo::instance()->update(['weekend_surcharge_percent' => 10]);

        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $checkIn  = $this->thursday();
        $friday   = $checkIn->copy()->addDay();
        $checkOut = $checkIn->copy()->addDays(3);

        SeasonalRate::factory()->create([
            'room_type_id'     => $roomType->id,
            'start_date'       => $friday->toDateString(),
            'end_date'         => $friday->toDateString(),
            'adjustment_type'  => 'fixed_per_night',
            'adjustment_value' => 200000,
        ]);

        $result = $this->service->calculate($roomType, $checkIn->toDateString(), $checkOut->toDateString(), 1);

        // Fri: (1.000.000 + 200.000) * 1.10 = 1.320.000
        $this->assertEquals(1320000, $result['nightly_breakdown'][1]['nightly_total']);
    }

    public function test_child_surcharge_scales_with_children_and_nights(): void
    {
        HotelInfo::instance()->update(['child_surcharge_per_night' => 50000]);

        $roomType = RoomType::factory()->create(['price_per_night' => 1000000]);
        $checkIn  = $this->thursday();
        $checkOut = $checkIn->copy()->addDays(3);

        $result = $this->service->calculate($roomType, $checkIn->toDateString(), $checkOut->toDateString(), 1, children: 2);

        // 50.000 * 2 trẻ * 3 đêm = 300.000
        $this->assertEquals(300000, $result['child_surcharge']);
        $this->assertEquals($result['room_subtotal'] + 300000, $result['total_price']);
    }

    public function test_booking_creation_persists_price_breakdown_and_child_surcharge(): void
    {
        HotelInfo::instance()->update(['child_surcharge_per_night' => 50000]);

        $customer = User::factory()->customer()->create();
        $roomType = RoomType::factory()->create(['price_per_night' => 1000000, 'capacity' => 4]);
        $checkIn  = $this->thursday();
        $checkOut = $checkIn->copy()->addDays(2);

        $response = $this->actingAs($customer)->post('/customer/bookings', [
            'items'          => [['room_type_id' => $roomType->id, 'quantity' => 1, 'adults' => 2, 'children' => 1]],
            'check_in'       => $checkIn->toDateString(),
            'check_out'      => $checkOut->toDateString(),
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
        ]);

        $booking = $customer->bookings()->first();
        $response->assertRedirect(route('customer.bookings.show', $booking->id));

        $item = $booking->bookingItems->first();
        $this->assertEquals(100000, $item->child_surcharge); // 50.000 * 1 trẻ * 2 đêm
        $this->assertCount(2, $item->price_breakdown);
        $this->assertEquals(2000000 + 100000, $booking->total_amount);
    }
}
