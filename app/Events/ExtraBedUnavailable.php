<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\ExtraBedRequest;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Bắn SAU KHI transaction tạo booking đã commit (xem BookingService::create())
 * — không dispatch bên trong DB::transaction() để tránh listener xử lý một
 * booking có thể bị rollback ngay sau đó.
 */
class ExtraBedUnavailable
{
    use Dispatchable;

    public function __construct(
        public readonly Booking $booking,
        public readonly ExtraBedRequest $extraBedRequest,
    ) {}
}
