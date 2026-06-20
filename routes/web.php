<?php

use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\DatabaseController;
use App\Http\Controllers\Web\Admin\HotelInfoController;
use App\Http\Controllers\Web\Admin\RoomTypeController as AdminRoomTypeController;
use App\Http\Controllers\Web\Admin\UserController as AdminUserController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Web\HomeController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------
// Public — trang giới thiệu khách sạn
// ---------------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');

// ---------------------------------------------------------------
// Auth — đăng ký/đăng nhập khách hàng dùng chung view với admin/staff,
// chỉ khác URL truy cập (/admin/login chỉ là alias hiển thị cùng form).
// ---------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/customer/register', [AuthWebController::class, 'showRegister'])->name('register');
    Route::post('/customer/register', [AuthWebController::class, 'register']);

    Route::get('/customer/login', [AuthWebController::class, 'showLogin'])->name('login');
    Route::post('/customer/login', [AuthWebController::class, 'login']);

    Route::get('/admin/login', [AuthWebController::class, 'showLogin'])->name('admin.login');
});

Route::post('/logout', [AuthWebController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ---------------------------------------------------------------
// Customer — khu vực khách hàng
// ---------------------------------------------------------------
Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
});

// ---------------------------------------------------------------
// Admin / Staff — khu vực quản trị
// ---------------------------------------------------------------
Route::middleware(['auth', 'role:admin,staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/database', [DatabaseController::class, 'index'])->name('database');

    Route::get('/hotel-info', [HotelInfoController::class, 'edit'])->name('hotel-info.edit');
    Route::put('/hotel-info', [HotelInfoController::class, 'update'])->name('hotel-info.update');

    Route::prefix('room-types')->name('room-types.')->group(function () {
        Route::get('/', [AdminRoomTypeController::class, 'index'])->name('index');
        Route::get('/create', [AdminRoomTypeController::class, 'create'])->name('create');
        Route::post('/', [AdminRoomTypeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminRoomTypeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminRoomTypeController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminRoomTypeController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore', [AdminRoomTypeController::class, 'restore'])->name('restore');
        Route::patch('/{id}/toggle-status', [AdminRoomTypeController::class, 'toggleStatus'])->name('toggle-status');
    });

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');

    // Chỉ admin mới khóa/mở khóa tài khoản (staff không được khóa lẫn nhau)
    Route::middleware('role:admin')->group(function () {
        Route::patch('/users/{id}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
    });
});
