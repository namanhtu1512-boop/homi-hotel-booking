<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use App\Services\AuditLogService;
use App\Services\ReviewService;
use App\Services\RoomTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class RoomTypeController extends Controller
{
    public function __construct(
        private readonly RoomTypeService $roomTypeService,
        private readonly AuditLogService $auditLog,
        private readonly ReviewService $reviewService,
    ) {}

    public function index(Request $request): View
    {
        $checkIn  = $request->query('check_in');
        $checkOut = $request->query('check_out');

        $dateRangeError = null;

        // Chỉ validate khi khách có điền ít nhất 1 trong 2 ô — cả 2 trống thì
        // giữ nguyên hành vi mặc định (không lọc theo khoảng ngày), không báo lỗi.
        if ($checkIn || $checkOut) {
            $validator = Validator::make(
                ['check_in' => $checkIn, 'check_out' => $checkOut],
                [
                    'check_in'  => ['required', 'date'],
                    'check_out' => ['required', 'date', 'after:check_in'],
                ],
                [],
                ['check_in' => 'từ ngày', 'check_out' => 'đến ngày']
            );

            if ($validator->fails()) {
                $dateRangeError = $validator->errors()->first();
            }
        }

        $rangeApplied = $checkIn && $checkOut && ! $dateRangeError;

        return view('staff.room-types.index', [
            'roomTypes' => $this->roomTypeService->adminIndexWithAvailability(
                $rangeApplied ? $checkIn : null,
                $rangeApplied ? $checkOut : null,
            ),
            'filters'          => ['check_in' => $checkIn, 'check_out' => $checkOut],
            'dateRangeApplied' => $rangeApplied,
            'dateRangeError'   => $dateRangeError,
        ]);
    }

    public function show(int $id): View
    {
        $roomType = $this->roomTypeService->find($id);

        return view('staff.room-types.show', [
            'roomType'      => $roomType,
            'amenityTiers'  => $this->roomTypeService->amenityTiers($roomType),
            'reviews'       => $this->reviewService->forRoomType($roomType->id),
            'reviewSummary' => $this->reviewService->summaryFor($roomType->id),
        ]);
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $roomType = RoomType::findOrFail($id);

        $roomType = $this->roomTypeService->toggleStatus($roomType);

        $this->auditLog->log('room_type.status_toggled', $roomType, "Đổi trạng thái loại phòng \"{$roomType->name}\" thành \"{$roomType->status}\".");

        return redirect()
            ->route('staff.room-types.index')
            ->with('success', "Đã đổi trạng thái loại phòng \"{$roomType->name}\".");
    }
}
