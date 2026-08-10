<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
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
    public function list(?int $roomTypeId = null): Collection
    {
        $rooms = Room::with(['roomType', 'activeStay.bookingItem.booking', 'lastStay'])
            ->when($roomTypeId, fn ($q) => $q->where('room_type_id', $roomTypeId))
            ->orderBy('room_number')
            ->get();

        $rooms->each(fn (Room $room) => $room->occupancy_status = $this->occupancyStatus($room));

        return $rooms;
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
     * @return array{days: \Illuminate\Support\Collection<int, Carbon>, roomRows: \Illuminate\Support\Collection, roomTypeRows: \Illuminate\Support\Collection}
     */
    public function monthlyOccupancy(Carbon $month, ?int $roomTypeId = null): array
    {
        $start = $month->copy()->startOfMonth()->timezone('Asia/Ho_Chi_Minh')->startOfDay();
        $end   = $month->copy()->endOfMonth()->timezone('Asia/Ho_Chi_Minh')->startOfDay();
        $days  = collect(range(0, $start->diffInDays($end)))->map(fn (int $i) => $start->copy()->addDays($i));

        $rooms = Room::with(['roomType', 'bookingItemRooms' => function ($q) use ($start, $end) {
                $q->whereDate('checked_in_at', '<=', $end->toDateString())
                    ->where(function ($q2) use ($start) {
                        $q2->whereNull('checked_out_at')->orWhereDate('checked_out_at', '>=', $start->toDateString());
                    });
            }, 'bookingItemRooms.bookingItem.booking'])
            ->when($roomTypeId, fn ($q) => $q->where('room_type_id', $roomTypeId))
            ->orderBy('room_number')
            ->get();

        $roomRows = $rooms->map(function (Room $room) use ($days) {
            $cells = $days->map(function (Carbon $day) use ($room) {
                $stay = $room->bookingItemRooms->first(function ($bir) use ($day) {
                    $checkedIn  = $bir->checked_in_at->timezone('Asia/Ho_Chi_Minh')->startOfDay();
                    $checkedOut = $bir->checked_out_at?->timezone('Asia/Ho_Chi_Minh')->startOfDay();

                    return $day->gte($checkedIn) && ($checkedOut === null || $day->lte($checkedOut));
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
