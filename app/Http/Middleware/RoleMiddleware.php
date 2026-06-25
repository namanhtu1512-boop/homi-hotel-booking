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
                    'success'    => false,
                    'message'    => 'Bạn chưa đăng nhập.',
                    'error_code' => ErrorCode::UNAUTHENTICATED->value,
                ], 401);
            }

            return redirect()->route('admin.login');
        }

        if ($user->status === 'locked') {
            abort(403, 'Tài khoản của bạn đã bị khóa.');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        return $next($request);
    }
}
