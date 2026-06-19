<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Promotion;
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
        $promotions = $this->promotionService->list(adminView: true, perPage: 10);

        return view('admin.promotions.index', compact('promotions'));
    }

    public function create(): View
    {
        return view('admin.promotions.form', [
            'promotion' => null,
            'hotels'    => Hotel::orderBy('name')->get(),
        ]);
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
        $promotion = Promotion::findOrFail($id);

        return view('admin.promotions.form', [
            'promotion' => $promotion,
            'hotels'    => Hotel::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $promotion = Promotion::findOrFail($id);

        $data = $this->validatePromotion($request);

        $this->promotionService->update($promotion, $data);

        $this->auditLog->log('promotion.updated', $promotion, "Cập nhật khuyến mãi \"{$promotion->name}\".");

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', "Đã cập nhật khuyến mãi \"{$promotion->name}\".");
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $promotion = Promotion::findOrFail($id);

        $promotion = $this->promotionService->toggleStatus($promotion);

        $this->auditLog->log('promotion.status_toggled', $promotion, "Đổi trạng thái khuyến mãi \"{$promotion->name}\" thành \"{$promotion->status}\".");

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', "Đã cập nhật trạng thái khuyến mãi \"{$promotion->name}\".");
    }

    private function validatePromotion(Request $request): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type'        => ['required', 'in:promotion,announcement'],
            'valid_from'  => ['nullable', 'date'],
            'valid_to'    => ['nullable', 'date', 'after_or_equal:valid_from'],
            'hotel_id'    => ['nullable', 'integer', 'exists:hotels,id'],
        ], [], [
            'name'        => 'tên khuyến mãi',
            'description' => 'mô tả',
            'type'        => 'loại',
            'valid_from'  => 'ngày bắt đầu',
            'valid_to'    => 'ngày kết thúc',
            'hotel_id'    => 'khách sạn áp dụng',
        ]);
    }
}
