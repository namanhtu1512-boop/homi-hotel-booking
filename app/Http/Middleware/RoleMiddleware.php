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

            return redirect()->route('admin.login');
        }

        if ($user->status === 'locked') {
            abort(403, 'Tài khoản của bạn đã bị khóa.');
        }

        $isAdminRoute = in_array('admin', $roles) || in_array('staff', $roles);

        if (! in_array($user->role, $roles, true) || ($isAdminRoute && $request->session()->get('login_context') !== 'admin')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập trang này.',
                ], 403);
            }

            return redirect()->route('customer.dashboard');
        }

        return $next($request);
    }
}
