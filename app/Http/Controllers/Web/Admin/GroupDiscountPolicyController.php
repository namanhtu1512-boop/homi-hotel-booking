<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\GroupDiscountPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupDiscountPolicyController extends Controller
{
    public function __construct(
        private readonly GroupDiscountPolicyService $policyService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): View
    {
        return view('admin.group-discount-policies.index', ['policies' => $this->policyService->list()]);
    }

    public function create(): View
    {
        return view('admin.group-discount-policies.form', ['policy' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePolicy($request);

        $policy = $this->policyService->create($data);

        $this->auditLog->log('group_discount_policy.created', $policy, "Tạo chính sách ưu đãi đoàn \"{$policy->name}\" (≥{$policy->min_rooms} phòng, {$policy->discount_percent}%).");

        return redirect()
            ->route('admin.group-discount-policies.index')
            ->with('success', "Đã tạo chính sách ưu đãi \"{$policy->name}\".");
    }

    public function edit(int $id): View
    {
        return view('admin.group-discount-policies.form', ['policy' => $this->policyService->find($id)]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $policy = $this->policyService->find($id);
        $data = $this->validatePolicy($request, $policy->id);

        $this->policyService->update($policy, $data);

        $this->auditLog->log('group_discount_policy.updated', $policy->fresh(), "Cập nhật chính sách ưu đãi đoàn \"{$policy->name}\".");

        return redirect()
            ->route('admin.group-discount-policies.index')
            ->with('success', "Đã cập nhật chính sách ưu đãi \"{$policy->name}\".");
    }

    public function destroy(int $id): RedirectResponse
    {
        $policy = $this->policyService->find($id);
        $name = $policy->name;

        $this->policyService->delete($policy);

        $this->auditLog->log('group_discount_policy.deleted', $policy, "Xóa chính sách ưu đãi đoàn \"{$name}\".");

        return redirect()
            ->route('admin.group-discount-policies.index')
            ->with('success', "Đã xóa chính sách ưu đãi \"{$name}\".");
    }

    public function restore(int $id): RedirectResponse
    {
        $policy = $this->policyService->find($id);

        $this->policyService->restore($policy);

        $this->auditLog->log('group_discount_policy.restored', $policy, "Khôi phục chính sách ưu đãi đoàn \"{$policy->name}\".");

        return redirect()
            ->route('admin.group-discount-policies.index')
            ->with('success', "Đã khôi phục chính sách ưu đãi \"{$policy->name}\".");
    }

    private function validatePolicy(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name'             => ['nullable', 'string', 'max:255'],
            'min_rooms'        => [
                'required', 'integer', 'min:1',
                Rule::unique('group_discount_policies', 'min_rooms')->ignore($ignoreId),
            ],
            'discount_percent' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'status'           => ['required', 'in:active,inactive'],
        ], [], [
            'name'             => 'tên chính sách',
            'min_rooms'        => 'số phòng tối thiểu',
            'discount_percent' => 'phần trăm giảm',
            'status'           => 'trạng thái',
        ]);

        $data['status'] ??= 'active';

        return $data;
    }
}
