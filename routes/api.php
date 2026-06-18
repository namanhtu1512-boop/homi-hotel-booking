<?php

use App\Http\Controllers\Api\Admin\AdminAuditLogController;
use App\Http\Controllers\Api\Admin\AdminHotelController;
use App\Http\Controllers\Api\Admin\AdminRoomTypeController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PublicHotelController;
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
    // PUBLIC — Khách sạn và tìm kiếm (không cần đăng nhập)
    // ---------------------------------------------------------------
    Route::get('/hotels',      [PublicHotelController::class, 'index']);
    Route::get('/hotels/{id}', [PublicHotelController::class, 'show']);

    // ---------------------------------------------------------------
    // PUBLIC — Kiểm tra phòng trống (không cần đăng nhập)
    // TODO Tuần 9: hoàn thiện AvailabilityService
    // ---------------------------------------------------------------
    Route::get('/hotels/{hotel}/availability', [BookingController::class, 'checkAvailability']);

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
    // Admin / Staff - quản lý khách sạn
    // ---------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:admin,staff'])->prefix('admin')->group(function () {
        Route::get('/hotels',                           [AdminHotelController::class, 'index']);
        Route::post('/hotels',                          [AdminHotelController::class, 'store']);
        Route::get('/hotels/{id}',                      [AdminHotelController::class, 'show']);
        Route::put('/hotels/{id}',                      [AdminHotelController::class, 'update']);
        Route::delete('/hotels/{id}',                   [AdminHotelController::class, 'destroy']);
        Route::post('/hotels/{id}/restore',             [AdminHotelController::class, 'restore']);
        Route::patch('/hotels/{id}/toggle-status',      [AdminHotelController::class, 'toggleStatus']);
        Route::delete('/hotels/{hotelId}/images/{imageId}', [AdminHotelController::class, 'destroyImage']);

        // Loại phòng theo khách sạn
        Route::get('/hotels/{hotelId}/room-types',  [AdminRoomTypeController::class, 'index']);
        Route::post('/hotels/{hotelId}/room-types', [AdminRoomTypeController::class, 'store']);

        // Loại phòng — thao tác theo room type ID
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
    // TODO Tuần 10-11: hoàn thiện logic tạo đơn và hủy đơn
    // ---------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
        Route::get('/bookings',                   [BookingController::class, 'myBookings']);
        Route::get('/bookings/{booking}',         [BookingController::class, 'show']);
        Route::post('/bookings',                  [BookingController::class, 'store']);
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    });

    // ---------------------------------------------------------------
    // ADMIN / STAFF — Quản lý tất cả đơn và thanh toán mô phỏng
    // TODO Tuần 12: hoàn thiện filter, cập nhật trạng thái, payment
    // ---------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:admin,staff'])->prefix('admin')->group(function () {
        Route::get('/bookings',                         [BookingController::class, 'adminIndex']);
        Route::get('/bookings/{booking}',               [BookingController::class, 'adminShow']);
        Route::put('/bookings/{booking}/status',        [BookingController::class, 'updateStatus']);
        Route::put('/bookings/{booking}/payment',       [BookingController::class, 'updatePayment']);
    });

});
