<?php

use App\Enums\ErrorCode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'   => \App\Http\Middleware\RoleMiddleware::class,
            'active' => \App\Http\Middleware\CheckActiveAccount::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );

        // Lỗi validation (422)
        $exceptions->render(function (
            \Illuminate\Validation\ValidationException $e,
            Request $request,
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'Dữ liệu không hợp lệ.',
                    'error_code' => ErrorCode::VALIDATION_ERROR->value,
                    'errors'     => $e->errors(),
                ], 422);
            }
        });

        // Chưa đăng nhập (401)
        $exceptions->render(function (
            \Illuminate\Auth\AuthenticationException $_e,
            Request $request,
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'Bạn chưa đăng nhập.',
                    'error_code' => ErrorCode::UNAUTHENTICATED->value,
                ], 401);
            }
        });

        // Không có quyền (403)
        $exceptions->render(function (
            \Illuminate\Auth\Access\AuthorizationException $e,
            Request $request,
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'message'    => $e->getMessage() ?: 'Bạn không có quyền thực hiện thao tác này.',
                    'error_code' => ErrorCode::UNAUTHORIZED->value,
                ], 403);
            }
        });

        // ModelNotFoundException — resource cụ thể không tìm thấy (404)
        $exceptions->render(function (
            \Illuminate\Database\Eloquent\ModelNotFoundException $_e,
            Request $request,
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'Không tìm thấy dữ liệu.',
                    'error_code' => ErrorCode::NOT_FOUND->value,
                ], 404);
            }
        });

        // Route không tồn tại (404)
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $_e,
            Request $request,
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'Đường dẫn API không tồn tại.',
                    'error_code' => ErrorCode::NOT_FOUND->value,
                ], 404);
            }
        });

        // Phương thức HTTP không được phép (405)
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $_e,
            Request $request,
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'Phương thức HTTP không được phép.',
                    'error_code' => ErrorCode::METHOD_NOT_ALLOWED->value,
                ], 405);
            }
        });

        // Gửi quá nhiều request — throttle (429)
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $_e,
            Request $request,
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau.',
                    'error_code' => ErrorCode::TOO_MANY_REQUESTS->value,
                ], 429);
            }
        });

        // Các HttpException khác (4xx/5xx từ abort())
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\HttpException $e,
            Request $request,
        ) {
            if ($request->is('api/*')) {
                $status  = $e->getStatusCode();
                $message = $e->getMessage() ?: match (true) {
                    $status === 503 => 'Hệ thống đang bảo trì. Vui lòng thử lại sau.',
                    $status >= 500  => 'Lỗi máy chủ. Vui lòng thử lại sau.',
                    default         => 'Đã xảy ra lỗi.',
                };

                return response()->json([
                    'success'    => false,
                    'message'    => $message,
                    'error_code' => ErrorCode::SERVER_ERROR->value,
                ], $status);
            }
        });

        // Fallback: mọi lỗi không xác định (500)
        $exceptions->render(function (
            \Throwable $_e,
            Request $request,
        ) {
            if ($request->is('api/*') && app()->environment('production')) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'Đã xảy ra lỗi không mong muốn. Vui lòng thử lại sau.',
                    'error_code' => ErrorCode::SERVER_ERROR->value,
                ], 500);
            }
        });
    })
    ->create();
