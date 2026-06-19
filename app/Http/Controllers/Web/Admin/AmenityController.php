<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Services\AmenityService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AmenityController extends Controller
{
    public function __construct(
        private readonly AmenityService $amenityService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): View
    {
        return view('admin.amenities.index', [
            'amenities' => $this->amenityService->list(),
        ]);
    }

    public function create(): View
    {
        return view('admin.amenities.form', ['amenity' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateAmenity($request);

        $amenity = $this->amenityService->create($data);

        $this->auditLog->log('amenity.created', $amenity, "Tạo tiện ích \"{$amenity->name}\".");

        return redirect()
            ->route('admin.amenities.index')
            ->with('success', "Đã tạo tiện ích \"{$amenity->name}\".");
    }

    public function edit(Amenity $amenity): View
    {
        return view('admin.amenities.form', compact('amenity'));
    }

    public function update(Request $request, Amenity $amenity): RedirectResponse
    {
        $data = $this->validateAmenity($request, $amenity->id);

        $this->amenityService->update($amenity, $data);

        $this->auditLog->log('amenity.updated', $amenity, "Cập nhật tiện ích \"{$amenity->name}\".");

        return redirect()
            ->route('admin.amenities.index')
            ->with('success', "Đã cập nhật tiện ích \"{$amenity->name}\".");
    }

    public function destroy(Amenity $amenity): RedirectResponse
    {
        $name = $amenity->name;

        $this->amenityService->delete($amenity);

        $this->auditLog->log('amenity.deleted', null, "Xóa tiện ích \"{$name}\".");

        return redirect()
            ->route('admin.amenities.index')
            ->with('success', "Đã xóa tiện ích \"{$name}\".");
    }

    private function validateAmenity(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:amenities,name' . ($ignoreId ? ",{$ignoreId}" : '')],
            'icon' => ['nullable', 'string', 'max:10'],
        ], [], [
            'name' => 'tên tiện ích',
            'icon' => 'biểu tượng',
        ]);
    }
}
