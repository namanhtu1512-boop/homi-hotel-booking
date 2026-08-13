<?php

namespace Tests\Feature\Admin;

use App\Models\GroupDiscountPolicy;
use App\Models\GroupDiscountRequest;
use App\Models\RoomType;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCreateByAdminTierDiscountTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BookingService
    {
        return app(BookingService::class);
    }

    private function baseData(RoomType $roomType, int $quantity): array
    {
        return [
            'check_in'       => now()->addDays(10)->toDateString(),
            'check_out'      => now()->addDays(12)->toDateString(),
            'customer_name'  => 'Đoàn Test',
            'customer_phone' => '0900000000',
            'items' => [[
                'room_type_id' => $roomType->id,
                'quantity'     => $quantity,
                'adults'       => $quantity * 2,
                'children'     => 0,
                'infants'      => 0,
            ]],
        ];
    }

    public function test_don_du_bac_duoc_tu_dong_giam_gia(): void
    {
        GroupDiscountPolicy::create(['name' => 'Bậc 10', 'min_rooms' => 10, 'discount_percent' => 5, 'status' => 'active']);
        $roomType = RoomType::factory()->create(['category' => 'standard', 'capacity' => 2, 'total_rooms' => 20, 'price_per_night' => 1000000]);

        $booking = $this->service()->createByAdmin($this->baseData($roomType, 15));

        $roomTotal = (float) $booking->bookingItems->sum('subtotal');
        $expectedDiscount = (int) round($roomTotal * 0.05);

        $this->assertSame($expectedDiscount, $booking->discount_amount);
        $this->assertEquals($roomTotal - $expectedDiscount, (float) $booking->total_amount);
        $this->assertEquals($roomTotal - $expectedDiscount, (float) $booking->payment->amount);

        $request = GroupDiscountRequest::where('booking_id', $booking->id)->first();
        $this->assertNotNull($request);
        $this->assertSame('policy_tier', $request->type);
        $this->assertSame('approved', $request->status);
        $this->assertNull($request->handled_by);
        $this->assertSame($expectedDiscount, $request->approved_amount);
    }

    public function test_don_khong_du_bac_khong_bi_giam(): void
    {
        GroupDiscountPolicy::create(['min_rooms' => 10, 'discount_percent' => 5, 'status' => 'active']);
        $roomType = RoomType::factory()->create(['category' => 'standard', 'capacity' => 2, 'total_rooms' => 20, 'price_per_night' => 1000000]);

        $booking = $this->service()->createByAdmin($this->baseData($roomType, 3));

        $this->assertSame(0, $booking->discount_amount);
        $this->assertEquals((float) $booking->bookingItems->sum('subtotal'), (float) $booking->total_amount);
        $this->assertSame(0, GroupDiscountRequest::where('booking_id', $booking->id)->count());
    }
}
