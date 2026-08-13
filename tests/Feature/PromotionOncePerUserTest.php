<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\RoomType;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PromotionOncePerUserTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BookingService
    {
        return app(BookingService::class);
    }

    private function baseData(RoomType $roomType, array $overrides = []): array
    {
        return array_merge([
            'check_in'       => now()->addDays(10)->toDateString(),
            'check_out'      => now()->addDays(12)->toDateString(),
            'customer_name'  => 'Test Promo Once',
            'customer_phone' => '0900000000',
            'items' => [[
                'room_type_id' => $roomType->id,
                'quantity'     => 1,
                'adults'       => 1,
                'children'     => 0,
                'infants'      => 0,
            ]],
        ], $overrides);
    }

    public function test_tai_khoan_dung_ma_lan_2_bi_chan(): void
    {
        $roomType  = RoomType::factory()->create(['category' => 'standard', 'capacity' => 2, 'total_rooms' => 5]);
        $customer  = User::factory()->create();
        $promotion = Promotion::factory()->create(['code' => 'ONCE10']);

        $booking = $this->service()->create($customer, $this->baseData($roomType, ['promo_codes' => ['ONCE10']]));

        $this->assertNotNull($booking);
        $this->assertSame($promotion->id, $booking->promotion_id);

        $this->expectException(ValidationException::class);

        try {
            $this->service()->create($customer, $this->baseData($roomType, ['promo_codes' => ['ONCE10']]));
        } catch (ValidationException $e) {
            $this->assertStringContainsString('đã sử dụng mã', $e->errors()['promo_codes'][0]);
            throw $e;
        }
    }

    public function test_tai_khoan_khac_van_dung_duoc_ma(): void
    {
        $roomType   = RoomType::factory()->create(['category' => 'standard', 'capacity' => 2, 'total_rooms' => 5]);
        $customerA  = User::factory()->create();
        $customerB  = User::factory()->create();
        Promotion::factory()->create(['code' => 'SHARE10']);

        $this->service()->create($customerA, $this->baseData($roomType, ['promo_codes' => ['SHARE10']]));
        $bookingB = $this->service()->create($customerB, $this->baseData($roomType, ['promo_codes' => ['SHARE10']]));

        $this->assertNotNull($bookingB);
    }

    public function test_dung_lai_duoc_ma_sau_khi_don_truoc_bi_huy(): void
    {
        $roomType  = RoomType::factory()->create(['category' => 'standard', 'capacity' => 2, 'total_rooms' => 5]);
        $customer  = User::factory()->create();
        Promotion::factory()->create(['code' => 'REUSE10']);

        $firstBooking = $this->service()->create($customer, $this->baseData($roomType, ['promo_codes' => ['REUSE10']]));
        $firstBooking->update(['status' => \App\Enums\BookingStatus::CANCELLED]);

        $secondBooking = $this->service()->create($customer, $this->baseData($roomType, ['promo_codes' => ['REUSE10']]));

        $this->assertNotNull($secondBooking);
    }
}
