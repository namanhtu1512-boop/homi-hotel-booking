<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckActiveAccount
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if ($user && $user->status === 'locked') {
            abort(403, 'Tài khoản của bạn đã bị khóa.');
        }

        return $next($request);
    }
}
