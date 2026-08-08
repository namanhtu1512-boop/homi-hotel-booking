<?php

namespace Tests\Feature;

use App\Models\RoomType;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingInfantLimitTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BookingService
    {
        return app(BookingService::class);
    }

    private function baseData(RoomType $roomType, array $itemOverrides = []): array
    {
        return [
            'check_in'       => now()->addDays(10)->toDateString(),
            'check_out'      => now()->addDays(12)->toDateString(),
            'customer_name'  => 'Test Infant Limit',
            'customer_phone' => '0900000000',
            'items' => [
                array_merge([
                    'room_type_id' => $roomType->id,
                    'quantity'     => 1,
                    'adults'       => 1,
                    'children'     => 0,
                    'infants'      => 0,
                ], $itemOverrides),
            ],
        ];
    }

    public function test_toi_da_2_so_sinh_moi_phong_duoc_chap_nhan(): void
    {
        $roomType = RoomType::factory()->create(['category' => 'standard', 'capacity' => 2, 'total_rooms' => 5]);

        $booking = $this->service()->createByAdmin($this->baseData($roomType, ['infants' => 2]));

        $this->assertNotNull($booking);
        $this->assertSame(2, $booking->bookingItems->first()->infants);
    }

    public function test_vuot_qua_2_so_sinh_1_phong_bi_chan(): void
    {
        $roomType = RoomType::factory()->create(['category' => 'standard', 'capacity' => 2, 'total_rooms' => 5]);

        $this->expectException(ValidationException::class);

        $this->service()->createByAdmin($this->baseData($roomType, ['infants' => 3]));
    }

    public function test_gioi_han_nhan_theo_so_phong_2_phong_toi_da_4_so_sinh(): void
    {
        $roomType = RoomType::factory()->create(['category' => 'standard', 'capacity' => 2, 'total_rooms' => 5]);

        $booking = $this->service()->createByAdmin($this->baseData($roomType, [
            'quantity' => 2,
            'adults'   => 2,
            'infants'  => 4,
        ]));

        $this->assertNotNull($booking);
        $this->assertSame(4, $booking->bookingItems->first()->infants);
    }

    public function test_2_phong_nhung_5_so_sinh_bi_chan(): void
    {
        $roomType = RoomType::factory()->create(['category' => 'standard', 'capacity' => 2, 'total_rooms' => 5]);

        $this->expectException(ValidationException::class);

        $this->service()->createByAdmin($this->baseData($roomType, [
            'quantity' => 2,
            'adults'   => 2,
            'infants'  => 5,
        ]));
    }

    public function test_gioi_han_ap_dung_dong_nhat_cho_family_category(): void
    {
        // Family trước đây có logic children/adults tách riêng, nhưng infants
        // phải áp dụng ĐỒNG NHẤT — không có ngoại lệ nào cho Family.
        $roomType = RoomType::factory()->create(['category' => 'family', 'capacity' => 4, 'total_rooms' => 5]);

        $this->expectException(ValidationException::class);

        $this->service()->createByAdmin($this->baseData($roomType, [
            'adults'  => 2,
            'infants' => 3,
        ]));
    }

    public function test_gioi_han_so_sinh_khong_lien_quan_gioi_han_tre_em(): void
    {
        // 2 sơ sinh (đúng giới hạn) không tính vào sức chứa/giới hạn trẻ em —
        // 2 giới hạn hoàn toàn độc lập nhau.
        $roomType = RoomType::factory()->create(['category' => 'standard', 'capacity' => 2, 'total_rooms' => 5]);

        $booking = $this->service()->createByAdmin($this->baseData($roomType, [
            'adults'   => 2,
            'children' => 0,
            'infants'  => 2,
        ]));

        $this->assertNotNull($booking);
    }

    public function test_thong_bao_loi_dung_noi_dung_va_field(): void
    {
        $roomType = RoomType::factory()->create(['name' => 'Phòng Test XYZ', 'category' => 'standard', 'capacity' => 2, 'total_rooms' => 5]);

        try {
            $this->service()->createByAdmin($this->baseData($roomType, ['infants' => 3]));
            $this->fail('Đáng lẽ phải throw ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('items.0.infants', $errors);
            $this->assertStringContainsString('Phòng Test XYZ', $errors['items.0.infants'][0]);
            $this->assertStringContainsString('tối đa 2 trẻ sơ sinh', $errors['items.0.infants'][0]);
        }
    }
}
