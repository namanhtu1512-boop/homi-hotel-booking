<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $users = $this->userService->list(
            filters: $request->only(['search', 'role', 'status']),
            perPage: 15,
        )->withQueryString();

        return view('admin.users.index', [
            'users'  => $users,
            'search' => $request->input('search', ''),
            'role'   => $request->input('role', ''),
            'status' => $request->input('status', ''),
        ]);
    }

    public function show(User $user): View
    {
        $user->loadCount('bookings');

        return view('admin.users.show', compact('user'));
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        try {
            $this->userService->toggleStatus($user, auth()->user());
        } catch (HttpException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $this->auditLog->log('user.status_toggled', $user, "Đổi trạng thái tài khoản \"{$user->name}\" thành \"{$user->status}\".");

        return redirect()
            ->back()
            ->with('success', "Đã cập nhật trạng thái tài khoản \"{$user->name}\".");
    }
}
