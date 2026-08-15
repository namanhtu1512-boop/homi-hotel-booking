<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromotionRequest;
use App\Services\AuditLogService;
use App\Services\PromotionRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PromotionRequestController extends Controller
{
    public function __construct(
        private readonly PromotionRequestService $promotionRequestService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function approve(int $id, Request $request): RedirectResponse
    {
        $promotionRequest = PromotionRequest::findOrFail($id);

        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:1000']]);

        try {
            $result = $this->promotionRequestService->approve($promotionRequest, $request->user(), $data['admin_note'] ?? null);
        } catch (ValidationException $e) {
            return redirect()->route('admin.promotions.index')->with('error', collect($e->errors())->flatten()->first());
        }

        $this->auditLog->log('promotion_request.approved', $result['promotion'], "Duyệt đề xuất mã ưu đãi khách quen \"{$result['promotion']->code}\" ({$result['promotion']->discount_percent}%) từ nhân viên — đã tạo khuyến mãi.");

        return redirect()->route('admin.promotions.index')->with('success', "Đã duyệt và kích hoạt mã ưu đãi \"{$result['promotion']->code}\".");
    }

    public function reject(int $id, Request $request): RedirectResponse
    {
        $promotionRequest = PromotionRequest::findOrFail($id);

        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:1000']]);

        try {
            $this->promotionRequestService->reject($promotionRequest, $request->user(), $data['admin_note'] ?? null);
        } catch (ValidationException $e) {
            return redirect()->route('admin.promotions.index')->with('error', collect($e->errors())->flatten()->first());
        }

        $this->auditLog->log('promotion_request.rejected', $promotionRequest->fresh(), "Từ chối đề xuất mã ưu đãi khách quen \"{$promotionRequest->code}\" từ nhân viên.");

        return redirect()->route('admin.promotions.index')->with('success', "Đã từ chối đề xuất mã \"{$promotionRequest->code}\".");
    }
}
