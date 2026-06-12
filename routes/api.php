<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn() => response()->json(['success' => true, 'status' => 'ok']));

Route::prefix('v1')->group(function () {

    // Auth - public
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    // Auth - cần đăng nhập
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',              [AuthController::class, 'me']);
        Route::put('/profile',         [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/logout',         [AuthController::class, 'logout']);
    });

});
