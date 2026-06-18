<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\Admin\DatabaseController;
use App\Http\Controllers\Web\Admin\HotelController;

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
    ->middleware(['auth', 'role:admin,staff'])
    ->name('admin.database');

Route::middleware(['auth', 'role:admin,staff'])->prefix('admin/hotels')->name('admin.hotels.')->group(function () {
    Route::get('/',               [HotelController::class, 'index'])->name('index');
    Route::get('/create',         [HotelController::class, 'create'])->name('create');
    Route::post('/',              [HotelController::class, 'store'])->name('store');
    Route::get('/{id}/edit',      [HotelController::class, 'edit'])->name('edit');
    Route::get('/{id}',           [HotelController::class, 'show'])->name('show');
    Route::put('/{id}',           [HotelController::class, 'update'])->name('update');
    Route::delete('/{id}',        [HotelController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/restore',  [HotelController::class, 'restore'])->name('restore');
    Route::patch('/{id}/toggle-status', [HotelController::class, 'toggleStatus'])->name('toggle-status');
});