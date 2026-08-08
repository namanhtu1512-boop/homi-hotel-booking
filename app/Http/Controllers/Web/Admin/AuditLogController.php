<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['user_id', 'action', 'created_from', 'created_to']);

        return view('admin.audit-logs.index', [
            'logs'    => $this->auditLogService->listForAdmin($filters, 20),
            'filters' => $filters,
            'staffs'  => User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']),
            'actions' => AuditLog::ACTION_LABELS,
        ]);
    }
}
