<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Bạn chưa đăng nhập',
                ], 401);
            }

            return redirect()->route('login');
        }

        if (!in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Bạn không có quyền truy cập chức năng này',
                ], 403);
            }

            abort(403, 'Bạn không có quyền truy cập trang này');
        }

        return $next($request);
    }
}