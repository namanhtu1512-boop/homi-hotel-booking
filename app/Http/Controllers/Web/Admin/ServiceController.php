<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\ServiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        private readonly ServiceService $serviceService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): View
    {
        return view('admin.services.index', ['services' => $this->serviceService->list()]);
    }

    public function create(): View
    {
        return view('admin.services.form', ['service' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateService($request);

        $service = $this->serviceService->create($data);

        $this->auditLog->log('service.created', $service, "Tạo dịch vụ \"{$service->name}\".");

        return redirect()
            ->route('admin.services.index')
            ->with('success', "Đã tạo dịch vụ \"{$service->name}\".");
    }

    public function edit(int $id): View
    {
        return view('admin.services.form', ['service' => $this->serviceService->find($id)]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $service = $this->serviceService->find($id);
        $data = $this->validateService($request);

        $this->serviceService->update($service, $data);

        $this->auditLog->log('service.updated', $service->fresh(), "Cập nhật dịch vụ \"{$service->name}\".");

        return redirect()
            ->route('admin.services.index')
            ->with('success', "Đã cập nhật dịch vụ \"{$service->name}\".");
    }

    public function destroy(int $id): RedirectResponse
    {
        $service = $this->serviceService->find($id);
        $name = $service->name;

        $this->serviceService->delete($service);

        $this->auditLog->log('service.deleted', $service, "Xóa dịch vụ \"{$name}\".");

        return redirect()
            ->route('admin.services.index')
            ->with('success', "Đã xóa dịch vụ \"{$name}\".");
    }

    public function restore(int $id): RedirectResponse
    {
        $service = $this->serviceService->find($id);

        $this->serviceService->restore($service);

        $this->auditLog->log('service.restored', $service, "Khôi phục dịch vụ \"{$service->name}\".");

        return redirect()
            ->route('admin.services.index')
            ->with('success', "Đã khôi phục dịch vụ \"{$service->name}\".");
    }

    private function validateService(Request $request): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price'       => ['required', 'numeric', 'min:0'],
            'status'      => ['required', 'in:active,hidden'],
        ], [], [
            'name'        => 'tên dịch vụ',
            'description' => 'mô tả',
            'price'       => 'giá',
            'status'      => 'trạng thái',
        ]);
    }
}
