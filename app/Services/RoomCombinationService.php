<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Tìm tổ hợp phòng thoả "đúng N phòng, tổng sức chứa >= M khách" từ một tập
 * ứng viên đã có sẵn (không tự query DB — RoomTypeService::searchCandidates()
 * chịu trách nhiệm lọc + tính available_quantity, nơi này chỉ làm bài toán
 * tối ưu tổ hợp thuần túy nên dễ unit-test độc lập).
 *
 * Bài toán: chọn x_i trong [0, available_quantity_i] sao cho Σx_i = N,
 * ưu tiên (1) Σ(x_i * capacity_i) >= M, (2) excess = Σcapacity - M nhỏ nhất,
 * (3) tổng giá nhỏ nhất. Giải bằng bounded-knapsack DP theo (số phòng, tổng
 * sức chứa) -> giá thấp nhất, N <= 10 (validate ở FilterRoomRequest) và
 * capacity mỗi phòng nhỏ nên không gian trạng thái rất nhỏ.
 *
 * Mỗi ứng viên trong $candidates là mảng kết hợp:
 *   ['room_type_id' => int, 'name' => string, 'category' => ?string,
 *    'capacity' => int, 'available_quantity' => int, 'price_per_night' => float]
 */
class RoomCombinationService
{
    /**
     * @param  Collection<int, array>  $candidates
     * @return array{status: string, ...}
     */
    public function find(Collection $candidates, int $roomsNeeded, ?int $guestsNeeded = null): array
    {
        $types = $candidates->filter(fn (array $c) => $c['available_quantity'] > 0 && $c['capacity'] > 0)->values();

        $totalAvailable = (int) $types->sum('available_quantity');

        if ($totalAvailable === 0) {
            return ['status' => 'no_availability'];
        }

        if ($totalAvailable < $roomsNeeded) {
            return [
                'status'    => 'insufficient_rooms',
                'available' => $totalAvailable,
                'needed'    => $roomsNeeded,
            ];
        }

        $maxCapacityPerRoom = (int) $types->max('capacity');
        $capMax = max($roomsNeeded * $maxCapacityPerRoom, $guestsNeeded ?? 0);

        [$dp, $steps] = $this->runDp($types, $roomsNeeded, $capMax);

        if ($guestsNeeded === null) {
            $bestCapacity = null;
            $bestPrice = INF;

            for ($c = 0; $c <= $capMax; $c++) {
                if ($dp[$roomsNeeded][$c] < $bestPrice) {
                    $bestPrice = $dp[$roomsNeeded][$c];
                    $bestCapacity = $c;
                }
            }

            return $this->buildOk($types, $steps, $roomsNeeded, $bestCapacity, $dp[$roomsNeeded][$bestCapacity], null);
        }

        for ($c = $guestsNeeded; $c <= $capMax; $c++) {
            if (is_finite($dp[$roomsNeeded][$c])) {
                return $this->buildOk($types, $steps, $roomsNeeded, $c, $dp[$roomsNeeded][$c], $guestsNeeded);
            }
        }

        $maxReachable = 0;
        for ($c = $capMax; $c >= 0; $c--) {
            if (is_finite($dp[$roomsNeeded][$c])) {
                $maxReachable = $c;
                break;
            }
        }

        return [
            'status'      => 'insufficient_capacity',
            'max_capacity' => $maxReachable,
            'needed'      => $guestsNeeded,
            'rooms_used'  => $roomsNeeded,
        ];
    }

    /**
     * Với mỗi category khác $excludeCategory trong $allCandidates, thử tìm tổ
     * hợp tự nó (không trộn category) đủ N phòng + M khách — dùng để gợi ý
     * "loại phòng khác đang còn khả dụng" khi category đang lọc không đủ,
     * KHÔNG tự động áp dụng thay cho khách hàng.
     *
     * @param  Collection<int, array>  $allCandidates
     * @return array<int, array{category: string, result: array}>
     */
    public function suggestAlternativeCategories(Collection $allCandidates, int $roomsNeeded, int $guestsNeeded, ?string $excludeCategory): array
    {
        return $allCandidates
            ->filter(fn (array $c) => ($c['category'] ?? null) !== null && $c['category'] !== $excludeCategory)
            ->groupBy('category')
            ->map(fn (Collection $group, string $category) => [
                'category' => $category,
                'result'   => $this->find($group, $roomsNeeded, $guestsNeeded),
            ])
            ->filter(fn (array $row) => $row['result']['status'] === 'ok')
            ->values()
            ->all();
    }

    /**
     * Chạy DP bounded-knapsack, trả về [dp, steps] để buildOk() dò ngược.
     * dp[r][c] = giá thấp nhất để đạt đúng r phòng, đúng c tổng sức chứa,
     * dùng hết các type đã xử lý. steps[i] lưu, cho từng ô (r,c) mà TYPE thứ
     * i (1-based, ứng $types[i-1]) là loại vừa cải thiện giá trị, số phòng x
     * đã lấy từ type đó để đạt ô (r,c) — dùng để dò ngược tổ hợp thắng, tránh
     * so sánh lại giá trị float (dễ sai số) khi backtrack.
     *
     * @param  Collection<int, array>  $types
     * @return array{0: array<int, array<int, float>>, 1: array<int, array<string, int>>}
     */
    private function runDp(Collection $types, int $roomsNeeded, int $capMax): array
    {
        $dp = array_fill(0, $roomsNeeded + 1, array_fill(0, $capMax + 1, INF));
        $dp[0][0] = 0.0;

        $steps = [];
        $K = $types->count();

        for ($i = 1; $i <= $K; $i++) {
            $type  = $types[$i - 1];
            $avail = (int) $type['available_quantity'];
            $cap   = (int) $type['capacity'];
            $price = (float) $type['price_per_night'];

            $prev = $dp;
            $steps[$i] = [];

            for ($r = 0; $r <= $roomsNeeded; $r++) {
                for ($c = 0; $c <= $capMax; $c++) {
                    if (! is_finite($prev[$r][$c])) {
                        continue;
                    }

                    $maxX = min($avail, $roomsNeeded - $r);

                    for ($x = 1; $x <= $maxX; $x++) {
                        $nr = $r + $x;
                        $nc = $c + $x * $cap;

                        if ($nc > $capMax) {
                            break;
                        }

                        $cand = $prev[$r][$c] + $x * $price;

                        if ($cand < $dp[$nr][$nc]) {
                            $dp[$nr][$nc] = $cand;
                            $steps[$i]["{$nr}:{$nc}"] = $x;
                        }
                    }
                }
            }
        }

        return [$dp, $steps];
    }

    /**
     * Dò ngược $steps từ (roomsNeeded, $capacity) ở bước cuối (K) về bước 0
     * để lấy danh sách (room_type_id, quantity) tạo nên tổ hợp thắng.
     *
     * @param  Collection<int, array>  $types
     * @param  array<int, array<string, int>>  $steps
     */
    private function buildOk(Collection $types, array $steps, int $roomsNeeded, int $capacity, float $price, ?int $guestsNeeded): array
    {
        $r = $roomsNeeded;
        $c = $capacity;
        $rooms = [];

        for ($i = $types->count(); $i >= 1; $i--) {
            $key = "{$r}:{$c}";
            $x = $steps[$i][$key] ?? 0;

            if ($x > 0) {
                $type = $types[$i - 1];

                $rooms[] = [
                    'room_type_id'  => $type['room_type_id'],
                    'name'          => $type['name'],
                    'category'      => $type['category'] ?? null,
                    'quantity'      => $x,
                    'capacity_each' => $type['capacity'],
                    'price_each'    => $type['price_per_night'],
                ];

                $r -= $x;
                $c -= $x * $type['capacity'];
            }
        }

        $rooms = array_reverse($rooms);

        return [
            'status'         => 'ok',
            'rooms'          => $rooms,
            'total_rooms'    => $roomsNeeded,
            'total_capacity' => $capacity,
            'total_price'    => $price,
            'excess'         => $guestsNeeded !== null ? $capacity - $guestsNeeded : 0,
        ];
    }
}
