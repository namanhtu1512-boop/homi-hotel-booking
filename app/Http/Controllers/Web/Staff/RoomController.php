<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use App\Services\RoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService,
    ) {}

    public function index(Request $request): View
    {
        return view('staff.rooms.index', [
            'rooms'     => $this->roomService->list($request->integer('room_type_id') ?: null),
            'roomTypes' => RoomType::orderBy('name')->get(),
            'filters'   => $request->only('room_type_id'),
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
