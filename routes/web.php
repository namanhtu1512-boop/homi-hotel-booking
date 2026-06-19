<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\Admin\AmenityController;
use App\Http\Controllers\Web\Admin\AuditLogController;
use App\Http\Controllers\Web\Admin\BookingController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\DatabaseController;
use App\Http\Controllers\Web\Admin\HotelController;
use App\Http\Controllers\Web\Admin\PaymentController;
use App\Http\Controllers\Web\Admin\PromotionController;
use App\Http\Controllers\Web\Admin\ReviewController;
use App\Http\Controllers\Web\Admin\RoomTypeController;
use App\Http\Controllers\Web\Admin\SettingsController;
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

Route::get('/home', [HomeController::class, 'index'])
    ->middleware('auth')
    ->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::get('/admin/database', [DatabaseController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.database');

Route::middleware(['auth', 'role:admin'])->prefix('admin/hotels')->name('admin.hotels.')->group(function () {
    Route::get('/',               [HotelController::class, 'index'])->name('index');
    Route::get('/create',         [HotelController::class, 'create'])->name('create');
    Route::post('/',              [HotelController::class, 'store'])->name('store');
    Route::get('/{id}/edit',      [HotelController::class, 'edit'])->name('edit');
    Route::get('/{id}',           [HotelController::class, 'show'])->name('show');
    Route::put('/{id}',           [HotelController::class, 'update'])->name('update');
    Route::patch('/{id}/toggle-status', [HotelController::class, 'toggleStatus'])->name('toggle-status');
    Route::delete('/{id}',        [HotelController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/restore',  [HotelController::class, 'restore'])->name('restore');
});

Route::get('/admin', [DashboardController::class, 'index'])
    ->middleware(['auth', 'role:admin,staff'])
    ->name('admin.dashboard');

Route::middleware(['auth', 'role:admin,staff'])->prefix('admin/bookings')->name('admin.bookings.')->group(function () {
    Route::get('/',                    [BookingController::class, 'index'])->name('index');
    Route::get('/{booking}',           [BookingController::class, 'show'])->name('show');
    Route::patch('/{booking}/confirm',   [BookingController::class, 'confirm'])->name('confirm');
    Route::patch('/{booking}/cancel',    [BookingController::class, 'cancel'])->name('cancel');
    Route::patch('/{booking}/check-in',  [BookingController::class, 'checkIn'])->name('check-in');
    Route::patch('/{booking}/check-out', [BookingController::class, 'checkOut'])->name('check-out');
});

Route::middleware(['auth', 'role:admin,staff'])->prefix('admin/room-types')->name('admin.room-types.')->group(function () {
    Route::get('/',               [RoomTypeController::class, 'index'])->name('index');
    Route::get('/{id}/edit',      [RoomTypeController::class, 'edit'])->name('edit');
    Route::put('/{id}',           [RoomTypeController::class, 'update'])->name('update');
    Route::patch('/{id}/toggle-status', [RoomTypeController::class, 'toggleStatus'])->name('toggle-status');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/room-types')->name('admin.room-types.')->group(function () {
    Route::get('/create',         [RoomTypeController::class, 'create'])->name('create');
    Route::post('/',              [RoomTypeController::class, 'store'])->name('store');
    Route::delete('/{id}',        [RoomTypeController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/restore',  [RoomTypeController::class, 'restore'])->name('restore');
});

Route::middleware(['auth', 'role:admin,staff'])->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('/',                     [UserController::class, 'index'])->name('index');
    Route::get('/{user}',               [UserController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/settings')->name('admin.settings.')->group(function () {
    Route::get('/',                  [SettingsController::class, 'index'])->name('index');
    Route::post('/general',          [SettingsController::class, 'updateGeneral'])->name('general');
    Route::post('/payment',          [SettingsController::class, 'updatePayment'])->name('payment');
    Route::post('/notification',     [SettingsController::class, 'updateNotification'])->name('notification');
    Route::post('/security',         [SettingsController::class, 'updateSecurity'])->name('security');
    Route::post('/password',         [SettingsController::class, 'updatePassword'])->name('password');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/promotions')->name('admin.promotions.')->group(function () {
    Route::get('/',               [PromotionController::class, 'index'])->name('index');
    Route::get('/create',         [PromotionController::class, 'create'])->name('create');
    Route::post('/',              [PromotionController::class, 'store'])->name('store');
    Route::get('/{id}/edit',      [PromotionController::class, 'edit'])->name('edit');
    Route::put('/{id}',           [PromotionController::class, 'update'])->name('update');
    Route::patch('/{id}/toggle-status', [PromotionController::class, 'toggleStatus'])->name('toggle-status');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/reviews')->name('admin.reviews.')->group(function () {
    Route::get('/',                          [ReviewController::class, 'index'])->name('index');
    Route::patch('/{review}/toggle-visibility', [ReviewController::class, 'toggleVisibility'])->name('toggle-visibility');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/amenities')->name('admin.amenities.')->group(function () {
    Route::get('/',                [AmenityController::class, 'index'])->name('index');
    Route::get('/create',          [AmenityController::class, 'create'])->name('create');
    Route::post('/',               [AmenityController::class, 'store'])->name('store');
    Route::get('/{amenity}/edit',  [AmenityController::class, 'edit'])->name('edit');
    Route::put('/{amenity}',       [AmenityController::class, 'update'])->name('update');
    Route::delete('/{amenity}',    [AmenityController::class, 'destroy'])->name('destroy');
});

Route::get('/admin/payments', [PaymentController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.payments.index');

Route::patch('/admin/payments/{payment}/confirm-cash', [PaymentController::class, 'confirmCash'])
    ->middleware(['auth', 'role:admin,staff'])
    ->name('admin.payments.confirm-cash');

Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.audit-logs.index');