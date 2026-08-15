<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class RoomService
{
    /**
     * Danh sách phòng vật lý cho trang "Phòng vật lý" (admin/staff) — kèm
     * thuộc tính runtime `occupancy_status` (không phải cột DB) cho cột
     * "Nhận / trả phòng": trạng thái lưu trú hôm nay + cảnh báo quá giờ trả
     * phòng, tính real-time từ Booking::isOverdueCheckout() mỗi lần gọi —
     * không phụ thuộc job quét nền (xem BookingService::flagOverdueCheckouts()).
     */
    public function list(?int $roomTypeId = null, ?string $occupancyState = null): Collection
    {
        $rooms = Room::with(['roomType', 'activeStay.bookingItem.booking', 'lastStay'])
            ->when($roomTypeId, fn ($q) => $q->where('room_type_id', $roomTypeId))
            ->orderBy('room_number')
            ->get();

        $rooms->each(fn (Room $room) => $room->occupancy_status = $this->occupancyStatus($room));

        // `occupancy_status` là thuộc tính runtime (không phải cột DB), nên lọc
        // bằng Collection::filter() sau khi tính, không phải bằng ->where().
        return $occupancyState
            ? $rooms->filter(fn (Room $room) => $room->occupancy_status['state'] === $occupancyState)->values()
            : $rooms;
    }

    /**
     * @return array{state: string, booking: ?\App\Models\Booking, since: ?\Carbon\Carbon, checked_out_at: ?\Carbon\Carbon}
     */
    private function occupancyStatus(Room $room): array
    {
        if ($room->activeStay) {
            $booking = $room->activeStay->bookingItem->booking;

            return [
                'state'          => $booking->isOverdueCheckout() ? 'overdue' : 'occupied',
                'booking'        => $booking,
                'since'          => $room->activeStay->checked_in_at,
                'checked_out_at' => null,
            ];
        }

        if ($room->lastStay?->checked_out_at?->timezone('Asia/Ho_Chi_Minh')->isToday()) {
            return [
                'state'          => 'checked_out_today',
                'booking'        => null,
                'since'          => null,
                'checked_out_at' => $room->lastStay->checked_out_at,
            ];
        }

        return ['state' => 'empty', 'booking' => null, 'since' => null, 'checked_out_at' => null];
    }

    /**
     * Dữ liệu cho trang "Lịch phòng" (bảng theo tháng) — trả về 2 khối tách
     * biệt vì phòng vật lý chỉ được gán cho khách lúc check-in (xem
     * BookingItem::rooms()/Room::activeStay()): trước khi check-in, một
     * booking chỉ biết loại phòng + số lượng, chưa có số phòng cụ thể.
     *
     *  - roomRows: theo TỪNG PHÒNG VẬT LÝ (nguồn: BookingItemRoom đã gán) —
     *    trả lời "phòng nào trả phòng ngày nào", chỉ phản ánh lượt ở THẬT
     *    (đã/đang check-in), không thấy được đặt phòng tương lai chưa nhận phòng.
     *  - roomTypeRows: theo TỪNG LOẠI PHÒNG (nguồn: Booking + BookingItem,
     *    mọi trạng thái còn giữ phòng — BookingStatus::holdingStatuses()) —
     *    trả lời "ngày nào còn lịch đặt", bao phủ cả đơn tương lai chưa check-in.
     *
     * @param  Carbon|null  $rangeStart  Khi truyền kèm $rangeEnd, ghi đè khoảng ngày hiển thị
     *                                    thay vì dùng trọn tháng của $month (bộ lọc "Từ ngày — Đến ngày").
     * @return array{days: \Illuminate\Support\Collection<int, Carbon>, roomRows: \Illuminate\Support\Collection, roomTypeRows: \Illuminate\Support\Collection}
     */
    public function monthlyOccupancy(Carbon $month, ?int $roomTypeId = null, ?Carbon $rangeStart = null, ?Carbon $rangeEnd = null): array
    {
        // Đổi múi giờ SANG Asia/Ho_Chi_Minh TRƯỚC khi lấy đầu/cuối tháng — nếu
        // đổi SAU, startOfMonth()/endOfMonth() chạy theo múi giờ gốc của
        // $month (mặc định UTC), rồi convert instant đó sang giờ VN (+7h) có
        // thể đẩy cuối tháng lệch sang ngày 1 tháng sau (cùng cạm bẫy double
        // timezone convert đã ghi trong Booking::todayForCheckoutComparison()).
        $monthVn = $month->copy()->timezone('Asia/Ho_Chi_Minh');
        $start   = ($rangeStart ?? $monthVn->copy()->startOfMonth())->timezone('Asia/Ho_Chi_Minh')->startOfDay();
        $end     = ($rangeEnd ?? $monthVn->copy()->endOfMonth())->timezone('Asia/Ho_Chi_Minh')->startOfDay();

        // Chặn khoảng ngày quá dài (bảng sẽ quá rộng để dùng được) — giới hạn tối đa 1 quý.
        if ($start->diffInDays($end) > 92) {
            $end = $start->copy()->addDays(92);
        }

        $days = collect(range(0, $start->diffInDays($end)))->map(fn (int $i) => $start->copy()->addDays($i));

        $rooms = Room::with(['roomType', 'bookingItemRooms' => function ($q) use ($start, $end) {
                $q->whereDate('checked_in_at', '<=', $end->toDateString())
                    ->where(function ($q2) use ($start) {
                        $q2->whereNull('checked_out_at')->orWhereDate('checked_out_at', '>=', $start->toDateString());
                    });
            }, 'bookingItemRooms.bookingItem.booking'])
            ->when($roomTypeId, fn ($q) => $q->where('room_type_id', $roomTypeId))
            ->orderBy('room_number')
            ->get();

        $today = Carbon::now('Asia/Ho_Chi_Minh')->startOfDay();

        $roomRows = $rooms->map(function (Room $room) use ($days, $today) {
            $cells = $days->map(function (Carbon $day) use ($room, $today) {
                $stay = $room->bookingItemRooms->first(function ($bir) use ($day, $today) {
                    $checkedIn = $bir->checked_in_at->timezone('Asia/Ho_Chi_Minh')->startOfDay();

                    if ($day->lt($checkedIn)) {
                        return false;
                    }

                    // Đã có ngày trả phòng THỰC TẾ — dùng luôn, đây là dữ liệu
                    // chắc chắn nhất (khách có thể nhận/trả phòng sớm/muộn hơn
                    // nhiều so với ngày ghi trên đơn, ví dụ đơn đặt 05-06 nhưng
                    // thực tế ở từ 04 tới 09 — bám theo ngày đặt sẽ làm mất
                    // đúng những ngày khách còn ở và bỏ sót mốc trả phòng thật).
                    if ($bir->checked_out_at !== null) {
                        return $day->lte($bir->checked_out_at->timezone('Asia/Ho_Chi_Minh')->startOfDay());
                    }

                    // Chưa có ngày trả phòng thực tế: chỉ chiếu "đang ở" tới
                    // ngày trả dự kiến trên đơn, hoặc tới HÔM NAY nếu đã quá
                    // hạn — không bao giờ lan sang những ngày/tháng CHƯA xảy ra
                    // (bug cũ: checked_out_at NULL bị chiếu "đang ở" vô hạn).
                    $scheduledCheckout = $bir->bookingItem->booking->check_out->timezone('Asia/Ho_Chi_Minh')->startOfDay();
                    $effectiveEnd = $scheduledCheckout->gt($today) ? $scheduledCheckout : $today;

                    return $day->lte($effectiveEnd);
                });

                if (! $stay) {
                    return ['state' => 'empty', 'booking' => null];
                }

                $booking = $stay->bookingItem->booking;

                if ($stay->checked_out_at?->timezone('Asia/Ho_Chi_Minh')->isSameDay($day)) {
                    return ['state' => 'checkout', 'booking' => $booking];
                }

                if (! $stay->checked_out_at && $booking->isOverdueCheckout()) {
                    return ['state' => 'overdue', 'booking' => $booking];
                }

                return ['state' => 'occupied', 'booking' => $booking];
            });

            return ['room' => $room, 'cells' => $cells];
        });

        $this->assignPendingReservationsToRooms($roomRows, $days, $start, $end);

        $roomTypes       = RoomType::when($roomTypeId, fn ($q) => $q->where('id', $roomTypeId))->orderBy('name')->get();
        $roomCountByType = Room::selectRaw('room_type_id, count(*) as c')->groupBy('room_type_id')->pluck('c', 'room_type_id');

        $bookings = Booking::with('bookingItems')
            ->whereIn('status', BookingStatus::holdingStatuses())
            ->whereDate('check_in', '<=', $end->toDateString())
            ->whereDate('check_out', '>=', $start->toDateString())
            ->get();

        $roomTypeRows = $roomTypes->map(function (RoomType $rt) use ($days, $bookings, $roomCountByType) {
            $total = (int) ($roomCountByType[$rt->id] ?? 0);

            $cells = $days->map(function (Carbon $day) use ($rt, $bookings, $total) {
                // So sánh THEO CHUỖI NGÀY (không phải instant Carbon) — check_in/
                // check_out là cột "date" thuần túy, còn $day neo giờ Việt Nam;
                // so sánh instant trực tiếp giữa 2 mốc khác múi giờ cho kết quả
                // lệch ngày y hệt cạm bẫy đã ghi trong Booking::todayForCheckoutComparison().
                $dayStr = $day->toDateString();

                $booked = $bookings->sum(function (Booking $b) use ($rt, $dayStr) {
                    if ($dayStr < $b->check_in->toDateString() || $dayStr >= $b->check_out->toDateString()) {
                        return 0;
                    }

                    return $b->bookingItems->where('room_type_id', $rt->id)->sum('quantity');
                });

                return ['booked' => (int) $booked, 'total' => $total];
            });

            return ['roomType' => $rt, 'cells' => $cells];
        });

        return ['days' => $days, 'roomRows' => $roomRows, 'roomTypeRows' => $roomTypeRows];
    }

    /**
     * Gán TẠM các đơn còn giữ chỗ nhưng CHƯA check-in vào phòng vật lý còn
     * trống cùng loại (chuyển ô từ 'empty' sang 'booked' — "Đã đặt", màu
     * xanh lá, phân biệt với "Đang ở" đã check-in thật). Hệ thống chỉ biết
     * đơn sẽ vào đúng phòng số mấy lúc lễ tân check-in, nên đây chỉ là gán
     * ảo phục vụ xem trước lịch (tham lam: đơn nhận phòng sớm hơn được chọn
     * phòng trống trước) — KHÔNG ghi CSDL, không ảnh hưởng việc gán phòng
     * thật lúc check-in.
     */
    private function assignPendingReservationsToRooms(\Illuminate\Support\Collection $roomRows, \Illuminate\Support\Collection $days, Carbon $start, Carbon $end): void
    {
        $roomsByType = $roomRows->groupBy(fn (array $row) => $row['room']->room_type_id);

        if ($roomsByType->isEmpty()) {
            return;
        }

        // room_id => [ngày (Y-m-d) => true] các ngày phòng đã bận, dù là do
        // check-in thật hay đã được gán ảo cho 1 đơn khác trong vòng lặp này.
        $busyDates = [];
        foreach ($roomRows as $row) {
            $busy = [];
            foreach ($row['cells'] as $idx => $cell) {
                if ($cell['state'] !== 'empty') {
                    $busy[$days[$idx]->toDateString()] = true;
                }
            }
            $busyDates[$row['room']->id] = $busy;
        }

        // Chỉ xem trước các đơn còn "sống" bình thường — CỐ Ý bỏ
        // EXPIRED_PENDING_CHECK (đã hết hạn giữ chỗ, chỉ còn chờ job quét tự
        // hủy hẳn) dù trạng thái này vẫn nằm trong holdingStatuses() và vẫn
        // được TÍNH vào số phòng còn trống ở bảng "Theo loại phòng" bên dưới
        // (đúng, để tránh bán trùng phòng lúc IPN VNPay có thể tới trễ) —
        // nhưng hiển thị nó như 1 booking bình thường ở đây (xanh lá/vàng)
        // gây hiểu lầm là còn khách thật sắp tới, trong khi đơn gần như chắc
        // chắn sẽ bị hủy trong vài phút tới.
        $previewableStatuses = array_diff(BookingStatus::holdingStatuses(), [BookingStatus::EXPIRED_PENDING_CHECK->value]);

        $pendingItems = BookingItem::query()
            ->whereIn('room_type_id', $roomsByType->keys())
            ->whereHas('booking', function ($q) use ($start, $end, $previewableStatuses) {
                $q->whereIn('status', $previewableStatuses)
                    ->whereDate('check_in', '<=', $end->toDateString())
                    ->whereDate('check_out', '>=', $start->toDateString());
            })
            ->with(['booking', 'bookingItemRooms'])
            ->get()
            ->sortBy(fn (BookingItem $item) => $item->booking->check_in);

        foreach ($pendingItems as $item) {
            $remaining = $item->quantity - $item->bookingItemRooms->count();

            if ($remaining <= 0) {
                continue;
            }

            $booking     = $item->booking;
            $checkInStr  = $booking->check_in->toDateString();
            $checkOutStr = $booking->check_out->toDateString();

            $rangeIdx = $days->keys()->filter(
                fn ($idx) => $days[$idx]->toDateString() >= $checkInStr && $days[$idx]->toDateString() < $checkOutStr
            );

            if ($rangeIdx->isEmpty()) {
                continue;
            }

            $rangeDates     = $rangeIdx->map(fn ($idx) => $days[$idx]->toDateString());
            $candidateRooms = $roomsByType->get($item->room_type_id, collect());

            for ($n = 0; $n < $remaining; $n++) {
                $targetRow = $candidateRooms->first(function (array $row) use ($busyDates, $rangeDates) {
                    foreach ($rangeDates as $d) {
                        if (! empty($busyDates[$row['room']->id][$d])) {
                            return false;
                        }
                    }

                    return true;
                });

                if (! $targetRow) {
                    break; // Hết phòng trống loại này cho khoảng ngày đó — không gán được.
                }

                foreach ($rangeIdx as $idx) {
                    $busyDates[$targetRow['room']->id][$days[$idx]->toDateString()] = true;
                    $targetRow['cells'][$idx] = ['state' => 'booked', 'booking' => $booking];
                }

                // Đánh dấu luôn NGÀY TRẢ PHÒNG DỰ KIẾN trên đơn (chưa check-in
                // nên chưa có checked_out_at thật) — chỉ khi ngày đó còn trong
                // phạm vi hiển thị và phòng vừa gán chưa bận sẵn đúng ngày đó
                // (tránh đè lên 1 lượt ở/trả phòng khác, ví dụ khách cũ trả
                // sáng — khách mới nhận chiều cùng ngày, không dùng lại được
                // đúng phòng này để hiển thị "trả phòng" cho cả 2 đơn).
                $checkoutIdx = $days->keys()->first(fn ($idx) => $days[$idx]->toDateString() === $checkOutStr);

                if ($checkoutIdx !== null && empty($busyDates[$targetRow['room']->id][$checkOutStr])) {
                    $busyDates[$targetRow['room']->id][$checkOutStr] = true;
                    $targetRow['cells'][$checkoutIdx] = ['state' => 'checkout', 'booking' => $booking];
                }
            }
        }
    }

    public function find(int $id): Room
    {
        return Room::findOrFail($id);
    }

    public function create(array $data): Room
    {
        return Room::create($data);
    }

    public function update(Room $room, array $data): Room
    {
        $room->update($data);

        return $room->fresh();
    }

    public function delete(Room $room): void
    {
        $room->delete();
    }

    public function updateHousekeepingStatus(Room $room, string $status): Room
    {
        $room->update(['housekeeping_status' => $status]);

        return $room->fresh();
    }

    /**
     * Phòng vật lý thuộc room_type này và hiện không có khách (không bị
     * chiếm bởi 1 đơn đang CHECKED_IN) — dùng để chọn khi check-in.
     */
    public function availableForRoomType(int $roomTypeId): Collection
    {
        return Room::where('room_type_id', $roomTypeId)
            ->orderBy('room_number')
            ->get()
            ->reject(fn (Room $room) => $room->isOccupied())
            ->values();
    }
}
