<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->input('action')}%");
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.audit-logs.index', [
            'logs'   => $logs,
            'action' => $request->input('action', ''),
            'userId' => $request->input('user_id', ''),
            'users'  => User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(),
        ]);
    }
}
