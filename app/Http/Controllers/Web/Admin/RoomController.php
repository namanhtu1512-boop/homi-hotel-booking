<?php

namespace App\Http\Controllers\Web\Admin;

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
        return view('admin.rooms.index', [
            'rooms'     => $this->roomService->list($request->integer('room_type_id') ?: null),
            'roomTypes' => RoomType::orderBy('name')->get(),
            'filters'   => $request->only('room_type_id'),
        ]);
    }

    public function create(): View
    {
        return view('admin.rooms.form', [
            'room'      => null,
            'roomTypes' => RoomType::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRoom($request);

        $room = $this->roomService->create($data);

        $this->auditLog->log('room.created', $room, "Tạo phòng \"{$room->room_number}\".");

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', "Đã tạo phòng \"{$room->room_number}\".");
    }

    public function edit(int $id): View
    {
        return view('admin.rooms.form', [
            'room'      => $this->roomService->find($id),
            'roomTypes' => RoomType::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $room = $this->roomService->find($id);
        $data = $this->validateRoom($request, $room->id);

        $this->roomService->update($room, $data);

        $this->auditLog->log('room.updated', $room->fresh(), "Cập nhật phòng \"{$room->room_number}\".");

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', "Đã cập nhật phòng \"{$room->room_number}\".");
    }

    public function destroy(int $id): RedirectResponse
    {
        $room = $this->roomService->find($id);
        $number = $room->room_number;

        $this->roomService->delete($room);

        $this->auditLog->log('room.deleted', null, "Xóa phòng \"{$number}\".");

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', "Đã xóa phòng \"{$number}\".");
    }

    public function updateHousekeeping(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'housekeeping_status' => ['required', 'in:clean,dirty,inspected,maintenance'],
        ]);

        $room = $this->roomService->find($id);
        $this->roomService->updateHousekeepingStatus($room, $data['housekeeping_status']);

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', "Đã cập nhật trạng thái dọn phòng \"{$room->room_number}\".");
    }

    private function validateRoom(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'room_number'  => ['required', 'string', 'max:20', 'unique:rooms,room_number,' . ($ignoreId ?? 'NULL') . ',id'],
        ], [], [
            'room_type_id' => 'loại phòng',
            'room_number'  => 'số phòng',
        ]);
    }
}
