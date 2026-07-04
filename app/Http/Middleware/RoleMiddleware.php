<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa đăng nhập.',
                ], 401);
            }

            $isAdminRoute = in_array('admin', $roles) || in_array('staff', $roles);

            return redirect()->route($isAdminRoute ? 'admin.login' : 'login');
        }

        if ($user->status === 'locked') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success'    => false,
                    'error_code' => 'ACCOUNT_LOCKED',
                    'message'    => 'Tài khoản của bạn đã bị khóa.',
                ], 403);
            }
            abort(403, 'Tài khoản của bạn đã bị khóa.');
        }

        $isAdminRoute = in_array('admin', $roles) || in_array('staff', $roles);

        if (! in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập trang này.',
                ], 403);
            }

            $redirectRoute = match ($user->role) {
                'admin' => 'admin.dashboard',
                'staff' => 'staff.dashboard',
                default => 'customer.dashboard',
            };

            return redirect()->route($redirectRoute);
        }

        if ($isAdminRoute && $request->hasSession() && $request->session()->get('login_context') !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập trang này.',
                ], 403);
            }

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
