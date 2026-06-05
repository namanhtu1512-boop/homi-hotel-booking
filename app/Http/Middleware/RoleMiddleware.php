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
            return response()->json([
                'message' => 'Bạn chưa đăng nhập',
            ], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Bạn không có quyền truy cập chức năng này',
            ], 403);
        }

        return $next($request);
    }
}