<?php

namespace Tests\Feature\Services;

use App\Services\RoomCombinationService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RoomCombinationServiceTest extends TestCase
{
    private function service(): RoomCombinationService
    {
        return new RoomCombinationService();
    }

    private function candidate(int $id, string $name, ?string $category, int $capacity, int $availableQuantity, float $price): array
    {
        return [
            'room_type_id'       => $id,
            'name'               => $name,
            'category'           => $category,
            'capacity'           => $capacity,
            'available_quantity' => $availableQuantity,
            'price_per_night'    => $price,
        ];
    }

    public function test_3_phong_6_khach_tim_duoc_to_hop_3_3_2(): void
    {
        // Chỉ 1 phòng capacity 2 -> buộc phải lấy 2 phòng capacity 3 mới đủ 3 phòng.
        $candidates = new Collection([
            $this->candidate(1, 'Standard', 'standard', 3, 5, 500_000),
            $this->candidate(2, 'Family', 'family', 2, 1, 400_000),
        ]);

        $result = $this->service()->find($candidates, 3, 6);

        $this->assertSame('ok', $result['status']);
        $this->assertSame(8, $result['total_capacity']);
        $this->assertSame(2, $result['excess']);

        $byType = collect($result['rooms'])->keyBy('room_type_id');
        $this->assertSame(2, $byType[1]['quantity']);
        $this->assertSame(1, $byType[2]['quantity']);
    }

    public function test_3_phong_6_khach_uu_tien_to_hop_vua_du_3_2_1(): void
    {
        // Cùng lúc có cả tổ hợp dư 2 (2x cap3 + 1x cap2) lẫn tổ hợp vừa đủ
        // (1x cap3 + 1x cap2 + 1x cap1) -> phải chọn tổ hợp dư ít nhất (excess=0).
        $candidates = new Collection([
            $this->candidate(1, 'Cap3', 'standard', 3, 5, 500_000),
            $this->candidate(2, 'Cap2', 'superior', 2, 5, 450_000),
            $this->candidate(3, 'Cap1', 'deluxe', 1, 5, 300_000),
        ]);

        $result = $this->service()->find($candidates, 3, 6);

        $this->assertSame('ok', $result['status']);
        $this->assertSame(6, $result['total_capacity']);
        $this->assertSame(0, $result['excess']);

        $byType = collect($result['rooms'])->keyBy('room_type_id');
        $this->assertSame(1, $byType[1]['quantity']);
        $this->assertSame(1, $byType[2]['quantity']);
        $this->assertSame(1, $byType[3]['quantity']);
    }

    public function test_cung_do_du_suc_chua_thi_uu_tien_gia_re_hon(): void
    {
        $candidates = new Collection([
            $this->candidate(1, 'Đắt', 'standard', 2, 5, 1_000_000),
            $this->candidate(2, 'Rẻ', 'superior', 2, 5, 500_000),
        ]);

        $result = $this->service()->find($candidates, 2, 4);

        $this->assertSame('ok', $result['status']);
        $this->assertSame(4, $result['total_capacity']);
        $this->assertSame(0, $result['excess']);
        $this->assertSame(1_000_000.0, $result['total_price']);

        $byType = collect($result['rooms'])->keyBy('room_type_id');
        $this->assertFalse(isset($byType[1]));
        $this->assertSame(2, $byType[2]['quantity']);
    }

    public function test_khong_du_tong_so_phong(): void
    {
        $candidates = new Collection([
            $this->candidate(1, 'Standard', 'standard', 3, 2, 500_000),
        ]);

        $result = $this->service()->find($candidates, 3, 6);

        $this->assertSame('insufficient_rooms', $result['status']);
        $this->assertSame(2, $result['available']);
        $this->assertSame(3, $result['needed']);
    }

    public function test_du_phong_nhung_khong_du_suc_chua(): void
    {
        $candidates = new Collection([
            $this->candidate(1, 'Standard', 'standard', 2, 5, 500_000),
        ]);

        $result = $this->service()->find($candidates, 3, 10);

        $this->assertSame('insufficient_capacity', $result['status']);
        $this->assertSame(6, $result['max_capacity']);
        $this->assertSame(10, $result['needed']);
        $this->assertSame(3, $result['rooms_used']);
    }

    public function test_loc_theo_hang_phong_khong_du_khong_tu_dong_chuyen_hang_khac(): void
    {
        $deluxeOnly = new Collection([
            $this->candidate(1, 'Deluxe', 'deluxe', 2, 1, 800_000),
        ]);

        $result = $this->service()->find($deluxeOnly, 3, 6);

        $this->assertSame('insufficient_rooms', $result['status']);

        $allCandidates = new Collection([
            $this->candidate(1, 'Deluxe', 'deluxe', 2, 1, 800_000),
            $this->candidate(2, 'Family', 'family', 4, 3, 900_000),
        ]);

        $alternatives = $this->service()->suggestAlternativeCategories($allCandidates, 3, 6, 'deluxe');

        $this->assertCount(1, $alternatives);
        $this->assertSame('family', $alternatives[0]['category']);
        $this->assertSame('ok', $alternatives[0]['result']['status']);
    }

    public function test_mot_hang_phong_co_nhieu_loai_phong_khac_capacity_van_tim_dung_to_hop(): void
    {
        // Category "deluxe" gồm 2 RoomType khác capacity — không được coi là
        // trường hợp đơn giản 1-loại-duy-nhất.
        $candidates = new Collection([
            $this->candidate(1, 'Deluxe View Biển', 'deluxe', 4, 2, 1_200_000),
            $this->candidate(2, 'Deluxe View Núi', 'deluxe', 2, 2, 900_000),
        ]);

        $result = $this->service()->find($candidates, 3, 8);

        $this->assertSame('ok', $result['status']);
        $this->assertSame(8, $result['total_capacity']);
        $this->assertSame(0, $result['excess']);

        $byType = collect($result['rooms'])->keyBy('room_type_id');
        $this->assertSame(1, $byType[1]['quantity']);
        $this->assertSame(2, $byType[2]['quantity']);
    }

    public function test_khong_con_phong_trong_nao(): void
    {
        $candidates = new Collection([
            $this->candidate(1, 'Standard', 'standard', 3, 0, 500_000),
        ]);

        $result = $this->service()->find($candidates, 2, 4);

        $this->assertSame('no_availability', $result['status']);
    }

    public function test_khong_truyen_so_khach_thi_khong_rang_buoc_suc_chua(): void
    {
        $candidates = new Collection([
            $this->candidate(1, 'Đắt', 'standard', 3, 5, 1_000_000),
            $this->candidate(2, 'Rẻ', 'superior', 3, 5, 400_000),
        ]);

        $result = $this->service()->find($candidates, 2, null);

        $this->assertSame('ok', $result['status']);
        $this->assertSame(800_000.0, $result['total_price']);
    }
}
