<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->latest('created_at');

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        return view('admin.audit-logs.index', [
            'logs'   => $query->paginate(20)->withQueryString(),
            'action' => $action ?? '',
        ]);
    }
}
