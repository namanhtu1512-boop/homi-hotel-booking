<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\Concerns\ValidatesImageText;
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
    use ValidatesImageText;

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

    public function toggleStatus(int $id): RedirectResponse
    {
        $roomType = RoomType::findOrFail($id);

        $roomType = $this->roomTypeService->toggleStatus($roomType);

        $this->auditLog->log('room_type.status_toggled', $roomType, "Đổi trạng thái loại phòng \"{$roomType->name}\" thành \"{$roomType->status}\".");

        return redirect()
            ->route('staff.room-types.index')
            ->with('success', "Đã đổi trạng thái loại phòng \"{$roomType->name}\".");
    }

    private function validateRoomType(Request $request): array
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'category'        => ['nullable', 'in:standard,superior,deluxe,family,suite'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'capacity'        => ['required', 'integer', 'min:1', 'max:255'],
            'bed_type'        => ['nullable', 'string', 'max:100'],
            'area'            => ['nullable', 'numeric', 'min:0'],
            'total_rooms'     => ['required', 'integer', 'min:1'],
            'images_text'     => ['nullable', 'string', $this->eachImageLineMax500()],
        ], [], [
            'name'            => 'tên loại phòng',
            'category'        => 'nhóm loại phòng',
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
