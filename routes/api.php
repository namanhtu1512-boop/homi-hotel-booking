<?php

use App\Http\Controllers\Api\Admin\AdminAuditLogController;
use App\Http\Controllers\Api\Admin\AdminHotelInfoController;
use App\Http\Controllers\Api\Admin\AdminRoomTypeController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PublicHotelInfoController;
use App\Http\Controllers\Api\PublicRoomTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function () {

    // ---------------------------------------------------------------
    // Auth - public
    // ---------------------------------------------------------------
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
    // PUBLIC — Thông tin khách sạn (singleton, không cần đăng nhập)
    // ---------------------------------------------------------------
    Route::get('/hotel-info', [PublicHotelInfoController::class, 'show']);

    // ---------------------------------------------------------------
    // PUBLIC — Danh sách & chi tiết loại phòng (BE2/BE3 Tuần 7)
    // ---------------------------------------------------------------
    Route::get('/room-types',      [PublicRoomTypeController::class, 'index']);
    Route::get('/room-types/{id}', [PublicRoomTypeController::class, 'show']);

    // ---------------------------------------------------------------
    // PUBLIC — Kiểm tra phòng trống (không cần đăng nhập)
    // ---------------------------------------------------------------
    Route::get('/room-types/{roomType}/availability', [BookingController::class, 'checkAvailability']);

    // ---------------------------------------------------------------
    // Admin / Staff - quản lý người dùng
    // ---------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:admin,staff'])->prefix('admin')->group(function () {
        Route::get('/users',        [AdminUserController::class, 'index']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
    });

    // Chỉ admin mới được khóa/mở khóa tài khoản và xem audit log
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus']);
        Route::get('/audit-logs', [AdminAuditLogController::class, 'index']);
    });

    // ---------------------------------------------------------------
    // Admin / Staff - quản lý thông tin khách sạn (singleton) và loại phòng
    // ---------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:admin,staff'])->prefix('admin')->group(function () {
        Route::get('/hotel-info',                       [AdminHotelInfoController::class, 'show']);
        Route::put('/hotel-info',                       [AdminHotelInfoController::class, 'update']);
        Route::patch('/hotel-info/toggle-maintenance',  [AdminHotelInfoController::class, 'toggleMaintenance']);
        Route::delete('/hotel-info/images/{imageId}',   [AdminHotelInfoController::class, 'destroyImage']);

        // Loại phòng (không còn gắn theo hotel vì chỉ có 1 khách sạn)
        Route::get('/room-types',  [AdminRoomTypeController::class, 'index']);
        Route::post('/room-types', [AdminRoomTypeController::class, 'store']);

        Route::get('/room-types/{id}',              [AdminRoomTypeController::class, 'show']);
        Route::put('/room-types/{id}',              [AdminRoomTypeController::class, 'update']);
        Route::delete('/room-types/{id}',           [AdminRoomTypeController::class, 'destroy']);
        Route::post('/room-types/{id}/restore',     [AdminRoomTypeController::class, 'restore']);
        Route::patch('/room-types/{id}/price',      [AdminRoomTypeController::class, 'updatePrice']);
        Route::patch('/room-types/{id}/inventory',  [AdminRoomTypeController::class, 'updateInventory']);
        Route::delete('/room-types/{roomTypeId}/images/{imageId}', [AdminRoomTypeController::class, 'destroyImage']);
    });

    // ---------------------------------------------------------------
    // CUSTOMER — Quản lý đơn đặt phòng của chính mình
    // ---------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
        Route::get('/bookings',                   [BookingController::class, 'myBookings']);
        Route::get('/bookings/{booking}',         [BookingController::class, 'show']);
        Route::post('/bookings',                  [BookingController::class, 'store']);
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    });

    // ---------------------------------------------------------------
    // ADMIN / STAFF — Quản lý tất cả đơn và thanh toán mô phỏng
    // ---------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:admin,staff'])->prefix('admin')->group(function () {
        Route::get('/bookings',                         [BookingController::class, 'adminIndex']);
        Route::get('/bookings/{booking}',               [BookingController::class, 'adminShow']);
        Route::put('/bookings/{booking}/status',        [BookingController::class, 'updateStatus']);
        Route::put('/bookings/{booking}/payment',       [BookingController::class, 'updatePayment']);
    });

});
