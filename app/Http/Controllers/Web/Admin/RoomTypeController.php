<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\CreateRoomTypeRequest;
use App\Http\Requests\RoomType\UpdateRoomTypeRequest;
use App\Models\RoomType;
use App\Services\AuditLogService;
use App\Services\RoomTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoomTypeController extends Controller
{
    public function __construct(
        private readonly RoomTypeService $roomTypeService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(): View
    {
        return view('admin.room-types.index', [
            'roomTypes' => $this->roomTypeService->list(adminView: true),
        ]);
    }

    public function create(): View
    {
        return view('admin.room-types.form', ['roomType' => null]);
    }

    public function store(CreateRoomTypeRequest $request): RedirectResponse
    {
        $data = $this->withImages($request);

        $roomType = $this->roomTypeService->create($data);
        $this->auditLogService->log('room_type.created', $roomType);

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã tạo loại phòng \"{$roomType->name}\".");
    }

    public function edit(int $id): View
    {
        $roomType = $this->roomTypeService->find($id);

        return view('admin.room-types.form', ['roomType' => $roomType]);
    }

    public function update(UpdateRoomTypeRequest $request, int $id): RedirectResponse
    {
        $roomType = RoomType::withTrashed()->findOrFail($id);

        $data = $this->withImages($request);

        $this->roomTypeService->update($roomType, $data);
        $this->auditLogService->log('room_type.updated', $roomType);

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã cập nhật loại phòng \"{$roomType->name}\".");
    }

    public function destroy(int $id): RedirectResponse
    {
        $roomType = RoomType::findOrFail($id);

        $this->roomTypeService->softDeleteOrDeactivate($roomType);
        $this->auditLogService->log('room_type.deleted', $roomType);

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã xóa/ẩn loại phòng \"{$roomType->name}\".");
    }

    public function restore(int $id): RedirectResponse
    {
        $roomType = RoomType::onlyTrashed()->findOrFail($id);

        $this->roomTypeService->restore($roomType);
        $this->auditLogService->log('room_type.restored', $roomType);

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã khôi phục loại phòng \"{$roomType->name}\".");
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $roomType = RoomType::findOrFail($id);

        $roomType = $this->roomTypeService->toggleStatus($roomType);
        $this->auditLogService->log('room_type.status_toggled', $roomType);

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã chuyển loại phòng \"{$roomType->name}\" sang trạng thái \"{$roomType->status}\".");
    }

    private function withImages($request): array
    {
        $data = $request->validated();

        $data['images'] = collect(explode("\n", $request->input('images_text', '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        return $data;
    }
}
