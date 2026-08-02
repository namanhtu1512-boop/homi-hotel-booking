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

            return redirect()->route($this->loginRouteFor($roles));
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

        // Admin và staff có phiên đăng nhập tách biệt (login_context 'admin'
        // / 'staff') — chặn phiên đăng nhập từ khu vực này bị dùng để truy
        // cập khu vực kia dù cùng nằm trong nhóm role được phép (vd route
        // API role:admin,staff chấp nhận cả hai context).
        $allowedContexts = array_values(array_intersect($roles, ['admin', 'staff']));

        if ($allowedContexts && $request->hasSession() && ! in_array($request->session()->get('login_context'), $allowedContexts, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập trang này.',
                ], 403);
            }

            return redirect()->route($this->loginRouteFor($roles));
        }

        return $next($request);
    }

    /**
     * Route đăng nhập tương ứng với nhóm role được phép của route hiện tại.
     * Ưu tiên staff.login khi route chỉ dành riêng cho staff; admin.login
     * cho route admin hoặc route dùng chung admin+staff (API).
     */
    private function loginRouteFor(array $roles): string
    {
        if (in_array('staff', $roles, true) && ! in_array('admin', $roles, true)) {
            return 'staff.login';
        }

        if (in_array('admin', $roles, true)) {
            return 'admin.login';
        }

        return 'login';
    }
}
