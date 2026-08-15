<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\BookingItem;
use App\Models\HotelInfo;

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
     * ngày cụ thể — dùng cho báo cáo "giường phụ đang sử dụng" của
     * admin/staff. Cùng điều kiện overlap với countAvailable() nhưng thu hẹp
     * về đúng 1 đêm (check_in <= date < check_out).
     */
    public function usageOnDate(string $date): array
    {
        $items = BookingItem::with(['roomType', 'rooms', 'booking'])
            ->where('extra_beds', '>', 0)
            ->whereHas('booking', fn ($q) => $q
                ->whereIn('status', BookingStatus::holdingStatuses())
                ->whereDate('check_in', '<=', $date)
                ->whereDate('check_out', '>', $date)
            )
            ->get()
            ->sortBy(fn ($item) => $item->booking->check_in)
            ->values();

        $total = HotelInfo::instance()->extra_beds_total;
        $used = (int) $items->sum('extra_beds');

        return [
            'date'      => $date,
            'total'     => $total,
            'used'      => $used,
            'available' => max(0, $total - $used),
            'items'     => $items,
        ];
    }
}
