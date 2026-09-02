<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\BookingItem;
use App\Models\HotelInfo;
use Carbon\Carbon;

/**
 * Giường phụ là 1 pool DÙNG CHUNG toàn khách sạn (hotel_info.extra_beds_total),
 * không tách theo từng loại phòng — khớp với cách sơ đồ nghiệp vụ mô tả
 * ("kiểm tra số giường phụ còn lại trong toàn bộ thời gian lưu trú").
 */
class ExtraBedInventoryService
{
    /**
     * Số giường phụ còn trống trong khoảng ngày — chỉ tính booking_items.extra_beds
     * ĐÃ ĐƯỢC CẤP (khác 0), không tính các dòng đang chờ tư vấn (status
     * pending_consultation nhưng extra_beds vẫn = 0 cho tới khi resolve —
     * xem BookingService::create(), ExtraBedRequestService::resolve()), nên
     * booking đang chờ không tự chiếm pool của chính nó hay của booking khác.
     */
    public function countAvailable(string $checkIn, string $checkOut, ?int $excludeBookingId = null): int
    {
        $used = (int) BookingItem::where('extra_beds', '>', 0)
            ->whereHas('booking', fn ($q) => $q
                ->whereIn('status', BookingStatus::holdingStatuses())
                ->whereDate('check_in', '<', $checkOut)
                ->whereDate('check_out', '>', $checkIn)
                ->when($excludeBookingId, fn ($q2) => $q2->where('id', '!=', $excludeBookingId))
            )
            ->sum('extra_beds');

        return max(0, HotelInfo::instance()->extra_beds_total - $used);
    }

    /**
     * Danh sách các dòng đơn (booking_items) đang chiếm giường phụ trong 1
     * khoảng ngày (start_date..end_date, cả 2 đầu đều tính) — dùng cho báo
     * cáo "giường phụ đang sử dụng" của admin/staff. $startDate == $endDate
     * thì tương đương xem đúng 1 ngày. Giới hạn tối đa 31 ngày (bảng theo
     * ngày sẽ quá rộng để dùng được nếu dài hơn).
     *
     * Vì pool giường phụ dùng chung, số "đang dùng"/"còn trống" biến động
     * theo từng đêm trong khoảng — nên trả thêm 'daily' (biến động theo
     * ngày) và 'used_peak'/'available_min' (điểm căng nhất trong khoảng) bên
     * cạnh danh sách 'items' (mọi dòng đơn có lưu trú giao với khoảng ngày).
     */
    public function usageInRange(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        if ($start->diffInDays($end) > 30) {
            $end = $start->copy()->addDays(30);
        }

        $items = BookingItem::with(['roomType', 'rooms', 'booking'])
            ->where('extra_beds', '>', 0)
            ->whereHas('booking', fn ($q) => $q
                ->whereIn('status', BookingStatus::holdingStatuses())
                ->whereDate('check_in', '<=', $end)
                ->whereDate('check_out', '>', $start)
            )
            ->get()
            ->sortBy(fn ($item) => $item->booking->check_in)
            ->values();

        $total = HotelInfo::instance()->extra_beds_total;

        $days = collect(range(0, $start->diffInDays($end)))->map(fn (int $i) => $start->copy()->addDays($i));

        $daily = $days->map(function (Carbon $day) use ($items, $total) {
            $used = (int) $items
                ->filter(fn ($item) => $item->booking->check_in->toDateString() <= $day->toDateString()
                    && $item->booking->check_out->toDateString() > $day->toDateString())
                ->sum('extra_beds');

            return [
                'date'      => $day,
                'used'      => $used,
                'available' => max(0, $total - $used),
            ];
        });

        return [
            'start_date'     => $start->toDateString(),
            'end_date'       => $end->toDateString(),
            'total'          => $total,
            'daily'          => $daily,
            'used_peak'      => (int) $daily->max('used'),
            'available_min'  => (int) $daily->min('available'),
            'items'          => $items,
        ];
    }
}
