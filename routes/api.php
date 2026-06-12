<?php

use App\Http\Controllers\Api\Admin\AdminUserController;
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

    // ---------------------------------------------------------------
    // Admin / Staff - quản lý người dùng
    // ---------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:admin,staff'])->prefix('admin')->group(function () {
        Route::get('/users',       [AdminUserController::class, 'index']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
    });

    // Chỉ admin mới được khóa/mở khóa tài khoản
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus']);
    });

});
