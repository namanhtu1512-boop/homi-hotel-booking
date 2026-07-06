<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use App\Models\SeasonalRate;
use App\Services\AuditLogService;
use App\Services\SeasonalRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SeasonalRateController extends Controller
{
    public function __construct(
        private readonly SeasonalRateService $seasonalRateService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): View
    {
        return view('admin.seasonal-rates.index', ['seasonalRates' => $this->seasonalRateService->list()]);
    }

    public function create(): View
    {
        return view('admin.seasonal-rates.form', [
            'seasonalRate' => null,
            'roomTypes'    => RoomType::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateSeasonalRate($request);

        $rate = $this->seasonalRateService->create($data);

        $this->auditLog->log('seasonal_rate.created', $rate, "Tạo bảng giá theo mùa \"{$rate->label}\".");

        return redirect()
            ->route('admin.seasonal-rates.index')
            ->with('success', "Đã tạo bảng giá theo mùa \"{$rate->label}\".");
    }

    public function edit(int $id): View
    {
        return view('admin.seasonal-rates.form', [
            'seasonalRate' => $this->seasonalRateService->find($id),
            'roomTypes'    => RoomType::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $seasonalRate = $this->seasonalRateService->find($id);
        $data = $this->validateSeasonalRate($request, $seasonalRate->id);

        $this->seasonalRateService->update($seasonalRate, $data);

        $this->auditLog->log('seasonal_rate.updated', $seasonalRate->fresh(), "Cập nhật bảng giá theo mùa \"{$seasonalRate->label}\".");

        return redirect()
            ->route('admin.seasonal-rates.index')
            ->with('success', "Đã cập nhật bảng giá theo mùa \"{$seasonalRate->label}\".");
    }

    public function destroy(int $id): RedirectResponse
    {
        $seasonalRate = $this->seasonalRateService->find($id);
        $label = $seasonalRate->label;

        $this->seasonalRateService->delete($seasonalRate);

        $this->auditLog->log('seasonal_rate.deleted', null, "Xóa bảng giá theo mùa \"{$label}\".");

        return redirect()
            ->route('admin.seasonal-rates.index')
            ->with('success', "Đã xóa bảng giá theo mùa \"{$label}\".");
    }

    private function validateSeasonalRate(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'room_type_id'     => ['nullable', 'integer', 'exists:room_types,id'],
            'label'            => ['required', 'string', 'max:255'],
            'start_date'       => ['required', 'date'],
            'end_date'         => ['required', 'date', 'after_or_equal:start_date'],
            'adjustment_type'  => ['required', 'in:percent,fixed_per_night'],
            'adjustment_value' => ['required', 'numeric', 'min:0'],
            'status'           => ['required', 'in:active,hidden'],
        ], [], [
            'room_type_id'     => 'loại phòng áp dụng',
            'label'            => 'tên đợt giá',
            'start_date'       => 'ngày bắt đầu',
            'end_date'         => 'ngày kết thúc',
            'adjustment_type'  => 'loại điều chỉnh',
            'adjustment_value' => 'giá trị điều chỉnh',
            'status'           => 'trạng thái',
        ]);

        // Không cho phép 2 rate active cùng phạm vi (cùng room_type_id, hoặc
        // cùng "áp dụng tất cả") chồng ngày — tránh mơ hồ khi tính giá.
        $overlaps = SeasonalRate::active()
            ->where('room_type_id', $data['room_type_id'] ?? null)
            ->where('start_date', '<=', $data['end_date'])
            ->where('end_date', '>=', $data['start_date'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($overlaps && $data['status'] === 'active') {
            throw ValidationException::withMessages([
                'start_date' => ['Đã có bảng giá theo mùa khác (cùng phạm vi) chồng khoảng ngày này.'],
            ]);
        }

        return $data;
    }
}
