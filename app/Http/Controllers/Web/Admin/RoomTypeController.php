<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\Concerns\ValidatesImageText;
use App\Models\Amenity;
use App\Models\RoomType;
use App\Services\AuditLogService;
use App\Services\RoomTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomTypeController extends Controller
{
    use ValidatesImageText;

    public function __construct(
        private readonly RoomTypeService $roomTypeService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): View
    {
        return view('admin.room-types.index', [
            'roomTypes' => $this->roomTypeService->adminIndexWithAvailability(),
        ]);
    }

    public function show(int $id): View
    {
        $roomType = $this->roomTypeService->find($id);

        return view('admin.room-types.show', ['roomType' => $roomType]);
    }

    public function create(): View
    {
        return view('admin.room-types.form', [
            'roomType'           => null,
            'amenities'          => Amenity::orderBy('name')->get(),
            'selectedAmenityIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRoomType($request);

        $roomType = $this->roomTypeService->create($data);

        $this->auditLog->log('room_type.created', $roomType, "Tạo loại phòng \"{$roomType->name}\".");

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã tạo loại phòng \"{$roomType->name}\".");
    }

    public function edit(int $id): View
    {
        $roomType = $this->roomTypeService->find($id);

        return view('admin.room-types.form', [
            'roomType'           => $roomType,
            'amenities'          => Amenity::orderBy('name')->get(),
            'selectedAmenityIds' => $roomType->amenities->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $roomType = RoomType::findOrFail($id);

        $data = $this->validateRoomType($request);

        $this->roomTypeService->update($roomType, $data);

        $this->auditLog->log('room_type.updated', $roomType->fresh(), "Cập nhật loại phòng \"{$roomType->name}\".");

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã cập nhật loại phòng \"{$roomType->name}\".");
    }

    public function destroy(int $id): RedirectResponse
    {
        $roomType = RoomType::findOrFail($id);
        $name     = $roomType->name;

        $this->roomTypeService->softDeleteOrDeactivate($roomType);

        $this->auditLog->log('room_type.deleted', $roomType, "Xóa loại phòng \"{$name}\".");

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã xóa loại phòng \"{$name}\".");
    }

    public function restore(int $id): RedirectResponse
    {
        $roomType = RoomType::onlyTrashed()->findOrFail($id);

        $this->roomTypeService->restore($roomType);

        $this->auditLog->log('room_type.restored', $roomType, "Khôi phục loại phòng \"{$roomType->name}\".");

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã khôi phục loại phòng \"{$roomType->name}\".");
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $roomType = RoomType::findOrFail($id);

        $updated = $this->roomTypeService->toggleStatus($roomType);

        $this->auditLog->log('room_type.status_toggled', $updated, "Đổi trạng thái loại phòng \"{$updated->name}\" thành \"{$updated->status}\".");

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã chuyển loại phòng \"{$updated->name}\" sang trạng thái \"{$updated->status}\".");
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
            'is_featured'     => ['nullable', 'boolean'],
            'images_text'     => ['nullable', 'string', $this->eachImageLineMax500()],
            'amenity_ids'     => ['nullable', 'array'],
            'amenity_ids.*'   => ['integer', 'exists:amenities,id'],
        ], [], [
            'name'            => 'tên loại phòng',
            'description'     => 'mô tả',
            'price_per_night' => 'giá theo đêm',
            'capacity'        => 'sức chứa',
            'bed_type'        => 'loại giường',
            'area'            => 'diện tích',
            'total_rooms'     => 'tổng số phòng',
            'is_featured'     => 'phòng nổi bật',
            'amenity_ids'     => 'tiện ích',
        ]);

        $data['images'] = collect(explode("\n", $data['images_text'] ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $data['is_featured'] = $request->boolean('is_featured');
        $data['amenity_ids'] = $data['amenity_ids'] ?? [];

        unset($data['images_text']);

        return $data;
    }
}
