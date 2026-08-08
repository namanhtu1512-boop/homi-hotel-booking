<?php

namespace App\Services;

use App\Models\RoomType;

/**
 * Sinh gợi ý phân bổ phòng cho form Đặt đoàn/nhóm — chỉ tham khảo, KHÔNG giữ
 * phòng. Tái sử dụng AvailabilityService::check() để lấy số phòng trống thực
 * tế thay vì viết lại query overlap.
 */
class GroupBookingSuggestionService
{
    public function __construct(
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * @return array{
     *     error?: string,
     *     max_capacity_available?: int,
     *     options?: array<int, array>,
     * }
     */
    public function suggest(string $checkIn, string $checkOut, int $numGuests): array
    {
        $roomTypes = $this->availableRoomTypes($checkIn, $checkOut);

        $maxCapacity = $roomTypes->sum(fn (array $rt) => $rt['available_quantity'] * $rt['occupancy']);

        if ($maxCapacity < $numGuests) {
            return [
                'error'                   => 'insufficient_availability',
                'max_capacity_available'  => $maxCapacity,
            ];
        }

        $options = collect([
            $this->tietKiem($roomTypes, $numGuests),
            $this->dongNhat($roomTypes, $numGuests),
            $this->itPhongNhat($roomTypes, $numGuests),
        ])->filter()->values();

        return ['options' => $options->all()];
    }

    /**
     * Danh sách loại phòng active kèm số phòng còn trống thực tế trong
     * khoảng ngày, chỉ giữ lại loại còn ít nhất 1 phòng trống.
     */
    private function availableRoomTypes(string $checkIn, string $checkOut): \Illuminate\Support\Collection
    {
        return RoomType::where('status', 'active')->get()
            ->map(function (RoomType $roomType) use ($checkIn, $checkOut) {
                $availableQuantity = $this->availability->check($roomType->id, $checkIn, $checkOut)['available_quantity'];

                return [
                    'id'                 => $roomType->id,
                    'name'               => $roomType->name,
                    'price'              => (float) $roomType->price_per_night,
                    'occupancy'          => (int) $roomType->capacity,
                    'available_quantity' => $availableQuantity,
                ];
            })
            ->filter(fn (array $rt) => $rt['available_quantity'] > 0 && $rt['occupancy'] > 0)
            ->values();
    }

    /**
     * Sắp xếp giá tăng dần, lấy dần từng loại đến khi đủ sức chứa >= num_guests.
     */
    private function tietKiem(\Illuminate\Support\Collection $roomTypes, int $numGuests): ?array
    {
        $sorted = $roomTypes->sortBy('price')->values();

        return $this->buildOption('tiet_kiem', 'Phương án tiết kiệm', $sorted, $numGuests);
    }

    /**
     * Chỉ chọn 1 loại phòng nếu số phòng trống của loại đó đủ đáp ứng
     * num_guests một mình. Nếu không loại nào đáp ứng được, bỏ qua phương án.
     */
    private function dongNhat(\Illuminate\Support\Collection $roomTypes, int $numGuests): ?array
    {
        $candidate = $roomTypes
            ->filter(fn (array $rt) => $rt['available_quantity'] * $rt['occupancy'] >= $numGuests)
            ->sortBy('price')
            ->first();

        if (! $candidate) {
            return null;
        }

        $quantity = (int) ceil($numGuests / $candidate['occupancy']);

        return $this->formatOption('dong_nhat', 'Phương án đồng nhất', [
            [
                'room_type_id'  => $candidate['id'],
                'room_type'     => $candidate['name'],
                'quantity'      => $quantity,
                'occupancy_each' => $candidate['occupancy'],
                'price_each'    => $candidate['price'],
            ],
        ]);
    }

    /**
     * Sắp xếp sức chứa giảm dần, lấy dần từng loại đến khi đủ — mục tiêu là
     * dùng ít phòng nhất có thể (mỗi phòng chứa được nhiều khách nhất trước).
     */
    private function itPhongNhat(\Illuminate\Support\Collection $roomTypes, int $numGuests): ?array
    {
        $sorted = $roomTypes->sortBy([
            ['occupancy', 'desc'],
            ['price', 'asc'],
        ])->values();

        return $this->buildOption('it_phong_nhat', 'Phương án ít phòng nhất', $sorted, $numGuests);
    }

    private function buildOption(string $key, string $label, \Illuminate\Support\Collection $sortedRoomTypes, int $numGuests): ?array
    {
        $remaining = $numGuests;
        $rooms     = [];

        foreach ($sortedRoomTypes as $rt) {
            if ($remaining <= 0) {
                break;
            }

            $needed = (int) ceil($remaining / $rt['occupancy']);
            $take   = min($needed, $rt['available_quantity']);

            if ($take <= 0) {
                continue;
            }

            $rooms[] = [
                'room_type_id'   => $rt['id'],
                'room_type'      => $rt['name'],
                'quantity'       => $take,
                'occupancy_each' => $rt['occupancy'],
                'price_each'     => $rt['price'],
            ];

            $remaining -= $take * $rt['occupancy'];
        }

        if ($remaining > 0 || empty($rooms)) {
            return null;
        }

        return $this->formatOption($key, $label, $rooms);
    }

    private function formatOption(string $key, string $label, array $rooms): array
    {
        $totalRooms    = array_sum(array_column($rooms, 'quantity'));
        $totalCapacity = array_sum(array_map(fn ($r) => $r['quantity'] * $r['occupancy_each'], $rooms));
        $totalPrice    = array_sum(array_map(fn ($r) => $r['quantity'] * $r['price_each'], $rooms));

        return [
            'key'                    => $key,
            'label'                  => $label,
            'rooms'                  => $rooms,
            'total_rooms'            => $totalRooms,
            'total_capacity'         => $totalCapacity,
            'estimated_total_price'  => $totalPrice,
            'note'                   => 'Giá tạm tính, chưa áp dụng khuyến mãi',
        ];
    }
}
