<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\DatabaseController;
use App\Http\Controllers\Web\Admin\HotelInfoController;
use App\Http\Controllers\Web\Admin\RoomTypeController;
use App\Http\Controllers\Web\Admin\UserController;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('home')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthWebController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthWebController::class, 'register']);

    Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthWebController::class, 'login']);
});

Route::post('/logout', [AuthWebController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Admin login (riêng biệt, không thuộc guest middleware)
Route::get('/admin/login', [AuthWebController::class, 'showAdminLogin'])->name('admin.login');
Route::post('/admin/login', [AuthWebController::class, 'adminLogin'])->name('admin.login.post');

Route::post('/admin/logout', [AuthWebController::class, 'adminLogout'])
    ->middleware('auth')
    ->name('admin.logout');

// ---------------------------------------------------------------
// CLIENT — Trang khách hàng (public-facing)
// ---------------------------------------------------------------
Route::get('/home', [HomeController::class, 'index'])
    ->middleware('auth')
    ->name('home');

Route::get('/dashboard', function () {
    return view('client.dashboard');
})->middleware('auth')->name('dashboard');

// ---------------------------------------------------------------
// ADMIN — Khu vực quản trị (admin/staff)
// ---------------------------------------------------------------
Route::middleware(['role:admin,staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/database',  [DatabaseController::class, 'index'])->name('database');

    Route::get('/hotel-info',                 [HotelInfoController::class, 'show'])->name('hotel-info.show');
    Route::get('/hotel-info/edit',            [HotelInfoController::class, 'edit'])->name('hotel-info.edit');
    Route::put('/hotel-info',                 [HotelInfoController::class, 'update'])->name('hotel-info.update');
    Route::patch('/hotel-info/toggle-maintenance', [HotelInfoController::class, 'toggleMaintenance'])->name('hotel-info.toggle-maintenance');

    Route::prefix('room-types')->name('room-types.')->group(function () {
        Route::get('/',               [RoomTypeController::class, 'index'])->name('index');
        Route::get('/create',         [RoomTypeController::class, 'create'])->name('create');
        Route::post('/',              [RoomTypeController::class, 'store'])->name('store');
        Route::get('/{id}/edit',      [RoomTypeController::class, 'edit'])->name('edit');
        Route::get('/{id}',           [RoomTypeController::class, 'show'])->name('show');
        Route::put('/{id}',           [RoomTypeController::class, 'update'])->name('update');
        Route::delete('/{id}',        [RoomTypeController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore',  [RoomTypeController::class, 'restore'])->name('restore');
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/',                     [UserController::class, 'index'])->name('index');
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
    });
});
