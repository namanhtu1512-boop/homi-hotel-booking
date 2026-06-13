<?php

namespace App\Http\Middleware;

use App\Enums\ErrorCode;
use Closure;
use Illuminate\Http\Request;

class CheckActiveAccount
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if ($user && $user->status === 'locked') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
                    'error_code' => ErrorCode::ACCOUNT_LOCKED->value,
                ], 403);
            }

            abort(403, 'Tài khoản của bạn đã bị khóa.');
        }

        return $next($request);
    }
}
