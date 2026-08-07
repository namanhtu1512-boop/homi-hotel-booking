<?php

namespace App\Listeners;

use App\Events\ExtraBedUnavailable;
use App\Models\RoomType;
use App\Services\AvailabilityService;

/**
 * KHÔNG implements ShouldQueue — cố ý chạy đồng bộ ngay khi event được bắn
 * (sau khi transaction tạo booking đã commit, xem BookingService::create()),
 * độc lập với NotifyStaffExtraBedUnavailable (chạy nền qua queue). Ghi kết
 * quả thẳng vào extra_bed_request.suggested_alternatives thay vì trả về qua
 * response — luồng thật là POST tạo booking → redirect → GET trang chi tiết,
 * nên không thể "trả ngược" dữ liệu qua chính request đang xử lý.
 */
class SuggestAlternativesToCustomer
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
    ) {}

    public function handle(ExtraBedUnavailable $event): void
    {
        $booking = $event->booking;
        $item    = $event->extraBedRequest->bookingItem ?? $booking->bookingItems->first();

        $alternatives = [
            ['type' => 'drop_extra_bed', 'label' => 'Bỏ yêu cầu giường phụ, giữ nguyên phòng đã chọn'],
        ];

        if ($item) {
            $largerRoomTypes = RoomType::where('status', 'active')
                ->where('capacity', '>', $item->roomType->capacity)
                ->get()
                ->filter(fn (RoomType $roomType) => $this->availabilityService->canBook(
                    $roomType->id, $booking->check_in->toDateString(), $booking->check_out->toDateString(), $item->quantity
                ))
                ->map(fn (RoomType $roomType) => [
                    'room_type_id' => $roomType->id,
                    'name'         => $roomType->name,
                    'capacity'     => $roomType->capacity,
                ])
                ->values();

            if ($largerRoomTypes->isNotEmpty()) {
                $alternatives[] = [
                    'type'    => 'upgrade_room',
                    'label'   => 'Đổi sang loại phòng lớn hơn',
                    'options' => $largerRoomTypes,
                ];
            }

            // quantity: 1 (không phải +1) — booking hiện tại đã ở trạng thái
            // holding (PENDING_CONSULTATION nằm trong holdingStatuses()) nên
            // getBookedQuantity() đã tính luôn quantity của chính item này;
            // chỉ cần hỏi "còn dư ít nhất 1 phòng cùng loại nữa không".
            $canAddRoom = $this->availabilityService->canBook(
                $item->room_type_id, $booking->check_in->toDateString(), $booking->check_out->toDateString(), 1
            );

            if ($canAddRoom) {
                $alternatives[] = ['type' => 'add_room', 'label' => 'Đặt thêm 1 phòng cùng loại để tách khách'];
            }
        }

        $alternatives[] = ['type' => 'waitlist', 'label' => 'Vào danh sách chờ giường phụ, giữ đơn để nhân viên liên hệ'];

        $event->extraBedRequest->update(['suggested_alternatives' => $alternatives]);
    }
}
