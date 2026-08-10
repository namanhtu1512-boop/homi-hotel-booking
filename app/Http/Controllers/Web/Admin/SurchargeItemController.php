<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\SurchargeItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurchargeItemController extends Controller
{
    public function __construct(
        private readonly SurchargeItemService $surchargeItemService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): View
    {
        return view('admin.surcharge-items.index', ['surchargeItems' => $this->surchargeItemService->list()]);
    }

    public function create(): View
    {
        return view('admin.surcharge-items.form', ['surchargeItem' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateSurchargeItem($request);

        $item = $this->surchargeItemService->create($data);

        $this->auditLog->log('surcharge_item.created', $item, "Tạo mục phụ phí phát sinh \"{$item->name}\".");

        return redirect()
            ->route('admin.surcharge-items.index')
            ->with('success', "Đã tạo mục phụ phí phát sinh \"{$item->name}\".");
    }

    public function edit(int $id): View
    {
        return view('admin.surcharge-items.form', ['surchargeItem' => $this->surchargeItemService->find($id)]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $item = $this->surchargeItemService->find($id);
        $data = $this->validateSurchargeItem($request);

        $this->surchargeItemService->update($item, $data);

        $this->auditLog->log('surcharge_item.updated', $item->fresh(), "Cập nhật mục phụ phí phát sinh \"{$item->name}\".");

        return redirect()
            ->route('admin.surcharge-items.index')
            ->with('success', "Đã cập nhật mục phụ phí phát sinh \"{$item->name}\".");
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = $this->surchargeItemService->find($id);
        $name = $item->name;

        $this->surchargeItemService->delete($item);

        $this->auditLog->log('surcharge_item.deleted', $item, "Xóa mục phụ phí phát sinh \"{$name}\".");

        return redirect()
            ->route('admin.surcharge-items.index')
            ->with('success', "Đã xóa mục phụ phí phát sinh \"{$name}\".");
    }

    public function restore(int $id): RedirectResponse
    {
        $item = $this->surchargeItemService->find($id);

        $this->surchargeItemService->restore($item);

        $this->auditLog->log('surcharge_item.restored', $item, "Khôi phục mục phụ phí phát sinh \"{$item->name}\".");

        return redirect()
            ->route('admin.surcharge-items.index')
            ->with('success', "Đã khôi phục mục phụ phí phát sinh \"{$item->name}\".");
    }

    private function validateSurchargeItem(Request $request): array
    {
        return $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'price'      => ['nullable', 'numeric', 'min:0'],
            'price_note' => ['nullable', 'string', 'max:255', 'required_without:price'],
            'status'     => ['required', 'in:active,hidden'],
        ], [
            'price_note.required_without' => 'Nhập giá cố định hoặc ghi chú khoảng giá (bắt buộc 1 trong 2).',
        ], [
            'name'       => 'tên mục phụ phí',
            'price'      => 'giá',
            'price_note' => 'ghi chú khoảng giá',
            'status'     => 'trạng thái',
        ]);
    }
}
