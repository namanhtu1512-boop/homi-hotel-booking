<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use App\Services\AuditLogService;
use App\Services\RoomTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoomTypeController extends Controller
{
    public function __construct(
        private readonly RoomTypeService $roomTypeService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): View
    {
        $roomTypes = $this->roomTypeService->list(adminView: true);

        $today = now()->toDateString();

        $bookedCounts = DB::table('booking_items')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->whereIn('bookings.status', ['pending', 'confirmed'])
            ->where('bookings.check_in', '<=', $today)
            ->where('bookings.check_out', '>', $today)
            ->groupBy('booking_items.room_type_id')
            ->pluck(DB::raw('SUM(booking_items.quantity)'), 'booking_items.room_type_id');

        $roomTypes->each(function (RoomType $room) use ($bookedCounts) {
            $room->available_today = max(0, $room->total_rooms - (int) $bookedCounts->get($room->id, 0));
        });

        return view('admin.room-types.index', ['roomTypes' => $roomTypes]);
    }

    public function show(int $id): View
    {
        $roomType = $this->roomTypeService->find($id);

        return view('admin.room-types.show', ['roomType' => $roomType]);
    }

    public function create(): View
    {
        return view('admin.room-types.form', ['roomType' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRoomType($request);

        $roomType = $this->roomTypeService->create($data);

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã tạo loại phòng \"{$roomType->name}\".");
    }

    public function edit(int $id): View
    {
        $roomType = $this->roomTypeService->find($id);

        return view('admin.room-types.form', ['roomType' => $roomType]);
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

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã xóa loại phòng \"{$name}\".");
    }

    public function restore(int $id): RedirectResponse
    {
        $roomType = RoomType::onlyTrashed()->findOrFail($id);

        $this->roomTypeService->restore($roomType);

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Đã khôi phục loại phòng \"{$roomType->name}\".");
    }

    private function validateRoomType(Request $request): array
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'capacity'        => ['required', 'integer', 'min:1'],
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
