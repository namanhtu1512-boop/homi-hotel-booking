<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(Request $request): View
    {
        $users = $this->userService->list(
            filters: $request->only(['search', 'role']),
            perPage: 15,
        );

        return view('admin.users.index', [
            'users'  => $users,
            'search' => $request->input('search', ''),
            'role'   => $request->input('role', ''),
        ]);
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $target = $this->userService->findOrFail($id);
        $this->userService->toggleStatus($target, Auth::user());
        $this->auditLogService->log('user.status_toggled', $target);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Đã cập nhật trạng thái tài khoản \"{$target->name}\".");
    }
}
