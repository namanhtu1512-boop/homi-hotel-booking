<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'             => ['required', 'string', 'max:50', 'alpha_dash'],
            'discount_percent' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'reason'           => ['nullable', 'string', 'max:1000'],
        ], [], [
            'code'             => 'mã',
            'discount_percent' => 'phần trăm giảm',
            'reason'           => 'lý do/ghi chú',
        ]);

        try {
            $promotionRequest = $this->promotionRequestService->propose($request->user(), $data);
        } catch (ValidationException $e) {
            return redirect()->route('staff.group-discount-requests.index')->with('error', collect($e->errors())->flatten()->first());
        }

        $this->auditLog->log('promotion_request.submitted', $promotionRequest, "Đề xuất mã ưu đãi khách quen \"{$promotionRequest->code}\" ({$promotionRequest->discount_percent}%) — chờ admin duyệt.");

        return redirect()->route('staff.group-discount-requests.index')->with('success', "Đã gửi đề xuất mã ưu đãi \"{$promotionRequest->code}\" — đang chờ admin duyệt.");
    }
}
