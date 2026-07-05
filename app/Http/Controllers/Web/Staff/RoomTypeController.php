<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use App\Services\AuditLogService;
use App\Services\RoomTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomTypeController extends Controller
{
    public function __construct(
        private readonly RoomTypeService $roomTypeService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): View
    {
        return view('staff.room-types.index', [
            'roomTypes' => $this->roomTypeService->adminIndexWithAvailability(),
        ]);
    }

    public function show(int $id): View
    {
        return view('staff.room-types.show', ['roomType' => $this->roomTypeService->find($id)]);
    }

    public function create(): View
    {
        return view('staff.room-types.form', ['roomType' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRoomType($request);

        $roomType = $this->roomTypeService->create($data);

        return redirect()
            ->route('staff.room-types.index')
            ->with('success', "Đã tạo loại phòng \"{$roomType->name}\".");
    }

    public function edit(int $id): View
    {
        return view('staff.room-types.form', ['roomType' => $this->roomTypeService->find($id)]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $roomType = RoomType::findOrFail($id);

        $data = $this->validateRoomType($request);

        $this->roomTypeService->update($roomType, $data);

        $this->auditLog->log('room_type.updated', $roomType->fresh(), "Cập nhật loại phòng \"{$roomType->name}\".");

        return redirect()
            ->route('staff.room-types.index')
            ->with('success', "Đã cập nhật loại phòng \"{$roomType->name}\".");
    }

    private function validateRoomType(Request $request): array
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'capacity'        => ['required', 'integer', 'min:1', 'max:255'],
            'bed_type'        => ['nullable', 'string', 'max:100'],
            'area'            => ['nullable', 'numeric', 'min:0'],
            'total_rooms'     => ['required', 'integer', 'min:1'],
            'images_text'     => ['nullable', 'string'],
        ], [], [
            'name'            => 'tên loại phòng',
            'description'     => 'mô tả',
            'price_per_night' => 'giá theo đêm',
            'capacity'        => 'sức chứa',
            'bed_type'        => 'loại giường',
            'area'            => 'diện tích',
            'total_rooms'     => 'tổng số phòng',
        ]);

        $data['images'] = collect(explode("\n", $data['images_text'] ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        unset($data['images_text']);

        return $data;
    }
}
