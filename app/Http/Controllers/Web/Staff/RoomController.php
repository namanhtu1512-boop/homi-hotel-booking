<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use App\Services\AuditLogService;
use App\Services\RoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        return view('staff.rooms.index', [
            'rooms'     => $this->roomService->list($request->integer('room_type_id') ?: null, $request->string('status')->toString() ?: null),
            'roomTypes' => RoomType::orderBy('name')->get(),
            'filters'   => $request->only('room_type_id', 'status'),
        ]);
    }

    public function calendar(Request $request): View
    {
        $month = $request->filled('month')
            ? \Carbon\Carbon::createFromFormat('Y-m', $request->string('month'), 'Asia/Ho_Chi_Minh')
            : now('Asia/Ho_Chi_Minh');

        $roomTypeId = $request->integer('room_type_id') ?: null;

        $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->string('start_date')) : null;
        $endDate   = $request->filled('end_date') ? \Carbon\Carbon::parse($request->string('end_date')) : null;

        if ($startDate && $endDate && $endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return view('staff.rooms.calendar', [
            'month'     => $month,
            'roomTypes' => RoomType::orderBy('name')->get(),
            'filters'   => $request->only('room_type_id', 'month', 'start_date', 'end_date'),
        ] + $this->roomService->monthlyOccupancy($month, $roomTypeId, $startDate, $endDate));
    }

    public function show(int $id): View
    {
        $room = $this->roomService->find($id);

        return view('staff.rooms.show', [
            'room'    => $room,
            'history' => $this->auditLog->forSubject($room),
        ]);
    }

    public function updateHousekeeping(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'housekeeping_status' => ['required', 'in:clean,dirty,inspected,maintenance'],
        ]);

        $room = $this->roomService->find($id);
        $this->roomService->updateHousekeepingStatus($room, $data['housekeeping_status']);

        return redirect()
            ->route('staff.rooms.index')
            ->with('success', "Đã cập nhật trạng thái dọn phòng \"{$room->room_number}\".");
    }
}
