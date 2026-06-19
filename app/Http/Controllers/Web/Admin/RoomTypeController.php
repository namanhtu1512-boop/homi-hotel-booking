<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
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

    public function index(Request $request): View
    {
        $roomTypes = $this->roomTypeService->adminList(
            filters: $request->only(['search', 'status', 'hotel_id']),
            perPage: 10,
        )->withQueryString();

        return view('admin.room-types.index', [
            'roomTypes' => $roomTypes,
            'hotels'    => Hotel::orderBy('name')->get(),
            'search'    => $request->input('search', ''),
            'status'    => $request->input('status', ''),
            'hotelId'   => $request->input('hotel_id', ''),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.room-types.form', [
            'roomType'        => null,
            'hotels'          => Hotel::active()->orderBy('name')->get(),
            'selectedHotelId' => $request->integer('hotel_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRoomType($request);

        $hotel = Hotel::findOrFail($data['hotel_id']);

        $roomType = $this->roomTypeService->create($hotel, $data);

        $this->auditLog->log('room_type.created', $roomType, "Tạo loại phòng \"{$roomType->name}\".");

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã tạo loại phòng \"{$roomType->name}\".");
    }

    public function edit(int $id): View
    {
        $roomType = $this->roomTypeService->find($id);

        return view('admin.room-types.form', [
            'roomType'        => $roomType,
            'hotels'          => Hotel::orderBy('name')->get(),
            'selectedHotelId' => $roomType->hotel_id,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $roomType = RoomType::findOrFail($id);

        $data = $this->validateRoomType($request, forUpdate: true);

        $this->roomTypeService->update($roomType, $data);

        $this->auditLog->log('room_type.updated', $roomType, "Cập nhật loại phòng \"{$roomType->name}\".");

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã cập nhật loại phòng \"{$roomType->name}\".");
    }

    public function destroy(int $id): RedirectResponse
    {
        $roomType = RoomType::findOrFail($id);
        $name = $roomType->name;

        $this->roomTypeService->softDeleteOrDeactivate($roomType);

        $this->auditLog->log('room_type.deleted', $roomType, "Xóa/ẩn loại phòng \"{$name}\".");

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã xóa/ẩn loại phòng \"{$name}\".");
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

        $roomType = $this->roomTypeService->toggleStatus($roomType);

        $this->auditLog->log('room_type.status_toggled', $roomType, "Đổi trạng thái loại phòng \"{$roomType->name}\" thành \"{$roomType->status}\".");

        return redirect()
            ->back()
            ->with('success', "Đã cập nhật trạng thái loại phòng \"{$roomType->name}\".");
    }

    private function validateRoomType(Request $request, bool $forUpdate = false): array
    {
        $data = $request->validate([
            'hotel_id'         => [$forUpdate ? 'sometimes' : 'required', 'integer', 'exists:hotels,id'],
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:5000'],
            'price_per_night'  => ['required', 'numeric', 'min:0'],
            'capacity'         => ['required', 'integer', 'min:1'],
            'bed_type'         => ['nullable', 'string', 'max:100'],
            'area'             => ['nullable', 'numeric', 'min:0'],
            'total_rooms'      => ['required', 'integer', 'min:1'],
            'images_text'      => ['nullable', 'string'],
        ], [], [
            'hotel_id'        => 'khách sạn',
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
