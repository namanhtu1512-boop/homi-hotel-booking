<?php

namespace Tests\Feature\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomHold;
use App\Models\RoomType;
use App\Services\RoomTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTypeServiceSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    private function service(): RoomTypeService
    {
        return app(RoomTypeService::class);
    }

    public function test_search_candidates_tru_ca_room_hold_dang_active(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);

        RoomHold::create([
            'room_type_id' => $roomType->id,
            'session_id'   => 'session-abc',
            'check_in'     => '2026-12-12',
            'check_out'    => '2026-12-15',
            'quantity'     => 3,
            'expires_at'   => now()->addMinutes(10),
        ]);

        $result = $this->service()->searchCandidates([
            'check_in'  => '2026-12-12',
            'check_out' => '2026-12-15',
        ]);

        $row = $result->firstWhere('id', $roomType->id);

        $this->assertSame(2, $row->available_quantity);
    }

    public function test_room_hold_da_het_han_khong_bi_tru(): void
    {
        $roomType = RoomType::factory()->create(['total_rooms' => 5]);

        RoomHold::create([
            'room_type_id' => $roomType->id,
            'session_id'   => 'session-abc',
            'check_in'     => '2026-12-12',
            'check_out'    => '2026-12-15',
            'quantity'     => 3,
            'expires_at'   => now()->subMinute(),
        ]);

        $result = $this->service()->searchCandidates([
            'check_in'  => '2026-12-12',
            'check_out' => '2026-12-15',
        ]);

        $row = $result->firstWhere('id', $roomType->id);

        $this->assertSame(5, $row->available_quantity);
    }

    public function test_loc_theo_category_chi_tra_ve_dung_hang_phong(): void
    {
        $deluxe = RoomType::factory()->create(['category' => 'deluxe']);
        RoomType::factory()->create(['category' => 'standard']);

        $result = $this->service()->searchCandidates(['category' => 'deluxe']);

        $this->assertCount(1, $result);
        $this->assertSame($deluxe->id, $result->first()->id);
    }

    public function test_sort_popularity_uu_tien_phong_duoc_dat_nhieu_nhat_va_loai_tru_don_da_huy(): void
    {
        $popular = RoomType::factory()->create();
        $unpopular = RoomType::factory()->create();

        $confirmedBooking = Booking::factory()->create(['status' => BookingStatus::CONFIRMED]);
        BookingItem::factory()->create([
            'booking_id'   => $confirmedBooking->id,
            'room_type_id' => $popular->id,
            'quantity'     => 5,
        ]);

        $cancelledBooking = Booking::factory()->cancelled()->create();
        BookingItem::factory()->create([
            'booking_id'   => $cancelledBooking->id,
            'room_type_id' => $popular->id,
            'quantity'     => 100,
        ]);

        $otherBooking = Booking::factory()->create(['status' => BookingStatus::CHECKED_OUT]);
        BookingItem::factory()->create([
            'booking_id'   => $otherBooking->id,
            'room_type_id' => $unpopular->id,
            'quantity'     => 1,
        ]);

        $result = $this->service()->searchCandidates(['sort' => 'popularity']);

        $this->assertSame($popular->id, $result->first()->id);
    }
}
