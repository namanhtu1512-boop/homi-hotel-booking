<?php

use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function () {

    // Auth - public
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    // Auth - cần đăng nhập + tài khoản không bị khóa
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
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

    // ---------------------------------------------------------------
    // PUBLIC — Kiểm tra phòng trống (không cần đăng nhập)
    // TODO Tuần 9: hoàn thiện AvailabilityService
    // ---------------------------------------------------------------
    Route::get('/hotels/{hotel}/availability', [BookingController::class, 'checkAvailability']);

    // ---------------------------------------------------------------
    // CUSTOMER — Quản lý đơn đặt phòng của chính mình
    // Yêu cầu: đăng nhập + role customer
    // TODO Tuần 10-11: hoàn thiện logic tạo đơn và hủy đơn
    // ---------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
        Route::get('/bookings',              [BookingController::class, 'myBookings']);
        Route::get('/bookings/{booking}',    [BookingController::class, 'show']);
        Route::post('/bookings',             [BookingController::class, 'store']);
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    });

    // ---------------------------------------------------------------
    // ADMIN / STAFF — Quản lý tất cả đơn và thanh toán mô phỏng
    // TODO Tuần 12: hoàn thiện filter, cập nhật trạng thái, payment
    // ---------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:admin,staff'])->prefix('admin')->group(function () {
        Route::get('/bookings',                          [BookingController::class, 'adminIndex']);
        Route::get('/bookings/{booking}',                [BookingController::class, 'adminShow']);
        Route::put('/bookings/{booking}/status',         [BookingController::class, 'updateStatus']);
        Route::put('/bookings/{booking}/payment',        [BookingController::class, 'updatePayment']);
    });

});
