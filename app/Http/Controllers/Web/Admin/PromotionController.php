<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\PromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(
        private readonly PromotionService $promotionService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): View
    {
        return view('admin.promotions.index', ['promotions' => $this->promotionService->list()]);
    }

    public function create(): View
    {
        return view('admin.promotions.form', ['promotion' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePromotion($request);

        $promotion = $this->promotionService->create($data);

        $this->auditLog->log('promotion.created', $promotion, "Tạo khuyến mãi \"{$promotion->name}\".");

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', "Đã tạo khuyến mãi \"{$promotion->name}\".");
    }

    public function edit(int $id): View
    {
        return view('admin.promotions.form', ['promotion' => $this->promotionService->find($id)]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $promotion = $this->promotionService->find($id);
        $data = $this->validatePromotion($request, $promotion->id);

        $this->promotionService->update($promotion, $data);

        $this->auditLog->log('promotion.updated', $promotion->fresh(), "Cập nhật khuyến mãi \"{$promotion->name}\".");

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', "Đã cập nhật khuyến mãi \"{$promotion->name}\".");
    }

    public function destroy(int $id): RedirectResponse
    {
        $promotion = $this->promotionService->find($id);
        $name = $promotion->name;

        $this->promotionService->delete($promotion);

        $this->auditLog->log('promotion.deleted', null, "Xóa khuyến mãi \"{$name}\".");

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', "Đã xóa khuyến mãi \"{$name}\".");
    }

    public function restore(int $id): RedirectResponse
    {
        $promotion = $this->promotionService->find($id);

        $this->promotionService->restore($promotion);

        $this->auditLog->log('promotion.restored', $promotion, "Khôi phục khuyến mãi \"{$promotion->name}\".");

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', "Đã khôi phục khuyến mãi \"{$promotion->name}\".");
    }

    private function validatePromotion(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'code'              => ['required', 'string', 'max:50', 'alpha_dash', 'unique:promotions,code,' . ($ignoreId ?? 'NULL') . ',id'],
            'description'       => ['nullable', 'string', 'max:2000'],
            'discount_percent'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount'   => ['nullable', 'integer', 'min:0'],
            'starts_at'         => ['nullable', 'date'],
            'ends_at'           => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status'            => ['required', 'in:active,hidden'],
        ], [], [
            'name'             => 'tên khuyến mãi',
            'code'             => 'mã khuyến mãi',
            'description'      => 'mô tả',
            'discount_percent' => 'phần trăm giảm',
            'discount_amount'  => 'số tiền giảm',
            'starts_at'        => 'ngày bắt đầu',
            'ends_at'          => 'ngày kết thúc',
            'status'           => 'trạng thái',
        ]);
    }
}
