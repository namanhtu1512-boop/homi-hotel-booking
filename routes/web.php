<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\RoomController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\HotelInfoController;
use App\Http\Controllers\Web\Admin\RoomTypeController;
use App\Http\Controllers\Web\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Web\Admin\UserController;
use App\Http\Controllers\Web\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Web\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Web\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Web\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Web\Admin\SeasonalRateController as AdminSeasonalRateController;
use App\Http\Controllers\Web\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Web\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Web\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Web\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Web\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Web\PromotionController;
use App\Http\Controllers\Web\NewsController;
use App\Http\Controllers\Web\ContactController;
use App\Http\Controllers\Web\GroupBookingController;
use App\Http\Controllers\Web\Admin\GroupBookingController as AdminGroupBookingController;
use App\Http\Controllers\Web\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Web\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Web\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Web\Customer\WishlistController as CustomerWishlistController;
use App\Http\Controllers\Web\Customer\ReviewController as CustomerReviewController;
use App\Http\Controllers\Web\Customer\ChatController as CustomerChatController;
use App\Http\Controllers\Web\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Web\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Web\Staff\HotelInfoController as StaffHotelInfoController;
use App\Http\Controllers\Web\Staff\RoomTypeController as StaffRoomTypeController;
use App\Http\Controllers\Web\Staff\RoomController as StaffRoomController;
use App\Http\Controllers\Web\Staff\BookingController as StaffBookingController;
use App\Http\Controllers\Web\Staff\PaymentController as StaffPaymentController;
use App\Http\Controllers\Web\Staff\ChatController as StaffChatController;
use App\Http\Controllers\Web\AboutController;

// ---------------------------------------------------------------
// Public
// ---------------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
Route::get('/rooms/{id}', [RoomController::class, 'show'])->name('rooms.show');
Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');
Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::get('/group-bookings', [GroupBookingController::class, 'show'])->name('group-bookings.show');
Route::post('/group-bookings', [GroupBookingController::class, 'store'])->middleware('throttle:5,1')->name('group-bookings.store');

// Health-check (Week 1 BE1)
Route::get('/health', fn () => response()->json(['status' => 'ok', 'timestamp' => now()->toISOString()]))->name('health');

// ---------------------------------------------------------------
// Auth — guest only
// ---------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/customer/register', [AuthWebController::class, 'showRegister'])->name('register');
    Route::post('/customer/register', [AuthWebController::class, 'register'])->middleware('throttle:5,1');

    Route::get('/customer/login', [AuthWebController::class, 'showLogin'])->name('login');
    Route::post('/customer/login', [AuthWebController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthWebController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Admin login (separate, not under guest middleware so already-logged-in admins get redirected)
Route::get('/admin/login', [AuthWebController::class, 'showAdminLogin'])->name('admin.login');
Route::post('/admin/login', [AuthWebController::class, 'adminLogin'])->middleware('throttle:5,1')->name('admin.login.post');

Route::post('/admin/logout', [AuthWebController::class, 'adminLogout'])
    ->middleware('auth')
    ->name('admin.logout');

// ---------------------------------------------------------------
// CUSTOMER — authenticated customers
// ---------------------------------------------------------------
Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');

    // Profile (Week 3 BE1)
    Route::get('/profile', [CustomerProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [CustomerProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [CustomerProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/email', [CustomerProfileController::class, 'updateEmail'])->name('profile.email');

    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/',          [CustomerBookingController::class, 'index'])->name('index');
        Route::get('/create',    [CustomerBookingController::class, 'create'])->name('create');
        Route::post('/',         [CustomerBookingController::class, 'store'])->name('store');
        Route::get('/{id}',      [CustomerBookingController::class, 'show'])->name('show');
        Route::get('/{id}/invoice', [CustomerBookingController::class, 'invoice'])->name('invoice');
        Route::post('/{id}/cancel', [CustomerBookingController::class, 'cancel'])->name('cancel');

        // Thanh toán tự phục vụ — chỉ khả dụng khi đơn đã được admin xác nhận
        // (xem Booking::canMarkPaymentAsPaid()).
        Route::post('/{id}/pay/online',        [CustomerBookingController::class, 'payOnline'])->name('pay-online');
        Route::post('/{id}/pay/bank-transfer', [CustomerBookingController::class, 'payBankTransfer'])->name('pay-bank-transfer');
        Route::post('/{id}/pay/deposit',       [CustomerBookingController::class, 'payDeposit'])->name('pay-deposit');
    });

    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/',            [CustomerWishlistController::class, 'index'])->name('index');
        Route::post('/{roomType}', [CustomerWishlistController::class, 'store'])->name('store');
        Route::patch('/{item}',    [CustomerWishlistController::class, 'update'])->name('update');
        Route::delete('/{item}',   [CustomerWishlistController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/create', [CustomerReviewController::class, 'create'])->name('create');
        Route::post('/',      [CustomerReviewController::class, 'store'])->name('store');
    });

    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/',      [CustomerChatController::class, 'index'])->name('index');
        Route::post('/',     [CustomerChatController::class, 'store'])->name('store')->middleware('throttle:30,1');
        Route::get('/poll',  [CustomerChatController::class, 'poll'])->name('poll');
    });
});

// ---------------------------------------------------------------
// ADMIN — chỉ admin (staff dùng khu vực riêng /staff/* bên dưới)
// ---------------------------------------------------------------
Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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

    Route::prefix('rooms')->name('rooms.')->group(function () {
        Route::get('/',                       [AdminRoomController::class, 'index'])->name('index');
        Route::get('/create',                 [AdminRoomController::class, 'create'])->name('create');
        Route::post('/',                      [AdminRoomController::class, 'store'])->name('store');
        Route::get('/{id}/edit',              [AdminRoomController::class, 'edit'])->name('edit');
        Route::put('/{id}',                   [AdminRoomController::class, 'update'])->name('update');
        Route::delete('/{id}',                [AdminRoomController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/housekeeping',    [AdminRoomController::class, 'updateHousekeeping'])->name('update-housekeeping');
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/',                     [UserController::class, 'index'])->name('index');
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
    });

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/',     [AdminCustomerController::class, 'index'])->name('index');
        Route::get('/{id}', [AdminCustomerController::class, 'show'])->name('show');
    });

    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/',                [AdminBookingController::class, 'index'])->name('index');
        Route::get('/{id}',            [AdminBookingController::class, 'show'])->name('show');
        Route::get('/{id}/invoice',    [AdminBookingController::class, 'invoice'])->name('invoice');
        Route::post('/{id}/confirm',   [AdminBookingController::class, 'confirm'])->name('confirm');
        Route::post('/{id}/cancel',    [AdminBookingController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/complete',  [AdminBookingController::class, 'complete'])->name('complete');
        Route::patch('/{id}/payment',  [AdminBookingController::class, 'updatePayment'])->name('update-payment');
        Route::get('/{id}/check-in',   [AdminBookingController::class, 'showCheckIn'])->name('check-in.show');
        Route::post('/{id}/check-in',  [AdminBookingController::class, 'checkIn'])->name('check-in');
        Route::post('/{id}/check-out', [AdminBookingController::class, 'checkOut'])->name('check-out');
    });

    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/',               [AdminPaymentController::class, 'index'])->name('index');
        Route::patch('/{id}/status',  [AdminPaymentController::class, 'updateStatus'])->name('update-status');
    });

    Route::prefix('promotions')->name('promotions.')->group(function () {
        Route::get('/',               [AdminPromotionController::class, 'index'])->name('index');
        Route::get('/create',         [AdminPromotionController::class, 'create'])->name('create');
        Route::post('/',              [AdminPromotionController::class, 'store'])->name('store');
        Route::get('/{id}/edit',      [AdminPromotionController::class, 'edit'])->name('edit');
        Route::put('/{id}',           [AdminPromotionController::class, 'update'])->name('update');
        Route::delete('/{id}',        [AdminPromotionController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore',  [AdminPromotionController::class, 'restore'])->name('restore');
    });

    Route::prefix('seasonal-rates')->name('seasonal-rates.')->group(function () {
        Route::get('/',          [AdminSeasonalRateController::class, 'index'])->name('index');
        Route::get('/create',    [AdminSeasonalRateController::class, 'create'])->name('create');
        Route::post('/',         [AdminSeasonalRateController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminSeasonalRateController::class, 'edit'])->name('edit');
        Route::put('/{id}',      [AdminSeasonalRateController::class, 'update'])->name('update');
        Route::delete('/{id}',   [AdminSeasonalRateController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/',               [AdminServiceController::class, 'index'])->name('index');
        Route::get('/create',         [AdminServiceController::class, 'create'])->name('create');
        Route::post('/',              [AdminServiceController::class, 'store'])->name('store');
        Route::get('/{id}/edit',      [AdminServiceController::class, 'edit'])->name('edit');
        Route::put('/{id}',           [AdminServiceController::class, 'update'])->name('update');
        Route::delete('/{id}',        [AdminServiceController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore',  [AdminServiceController::class, 'restore'])->name('restore');
    });

    Route::prefix('banners')->name('banners.')->group(function () {
        Route::get('/',          [AdminBannerController::class, 'index'])->name('index');
        Route::get('/create',    [AdminBannerController::class, 'create'])->name('create');
        Route::post('/',         [AdminBannerController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminBannerController::class, 'edit'])->name('edit');
        Route::put('/{id}',      [AdminBannerController::class, 'update'])->name('update');
        Route::delete('/{id}',   [AdminBannerController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/',                  [AdminReviewController::class, 'index'])->name('index');
        Route::patch('/{id}/toggle',     [AdminReviewController::class, 'toggleStatus'])->name('toggle');
        Route::delete('/{id}',           [AdminReviewController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('news')->name('news.')->group(function () {
        Route::get('/',          [AdminNewsController::class, 'index'])->name('index');
        Route::get('/create',    [AdminNewsController::class, 'create'])->name('create');
        Route::post('/',         [AdminNewsController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminNewsController::class, 'edit'])->name('edit');
        Route::put('/{id}',      [AdminNewsController::class, 'update'])->name('update');
        Route::delete('/{id}',   [AdminNewsController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('contact-messages')->name('contact-messages.')->group(function () {
        Route::get('/',               [AdminContactMessageController::class, 'index'])->name('index');
        Route::patch('/{id}/read',    [AdminContactMessageController::class, 'markRead'])->name('mark-read');
        Route::delete('/{id}',        [AdminContactMessageController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('group-bookings')->name('group-bookings.')->group(function () {
        Route::get('/',                    [AdminGroupBookingController::class, 'index'])->name('index');
        Route::patch('/{id}/mark-contacted', [AdminGroupBookingController::class, 'markContacted'])->name('mark-contacted');
        Route::delete('/{id}',             [AdminGroupBookingController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/',                 [AdminChatController::class, 'index'])->name('index');
        Route::get('/{customerId}',     [AdminChatController::class, 'show'])->name('show');
        Route::post('/{customerId}',    [AdminChatController::class, 'store'])->name('store')->middleware('throttle:30,1');
        Route::get('/{customerId}/poll', [AdminChatController::class, 'poll'])->name('poll');
    });
});

// ---------------------------------------------------------------
// STAFF — khu vực riêng, tách biệt hoàn toàn với admin (route/controller/
// view/layout riêng). Không có quản lý người dùng, xóa loại phòng, sửa
// thông tin khách sạn, hay xem database thô — những việc đó chỉ admin làm.
// ---------------------------------------------------------------
Route::middleware(['role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');

    Route::get('/hotel-info', [StaffHotelInfoController::class, 'show'])->name('hotel-info.show');

    Route::prefix('room-types')->name('room-types.')->group(function () {
        Route::get('/',          [StaffRoomTypeController::class, 'index'])->name('index');
        Route::get('/create',    [StaffRoomTypeController::class, 'create'])->name('create');
        Route::post('/',         [StaffRoomTypeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [StaffRoomTypeController::class, 'edit'])->name('edit');
        Route::get('/{id}',      [StaffRoomTypeController::class, 'show'])->name('show');
        Route::put('/{id}',      [StaffRoomTypeController::class, 'update'])->name('update');
    });

    Route::prefix('rooms')->name('rooms.')->group(function () {
        Route::get('/',                    [StaffRoomController::class, 'index'])->name('index');
        Route::patch('/{id}/housekeeping', [StaffRoomController::class, 'updateHousekeeping'])->name('update-housekeeping');
    });

    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/',                [StaffBookingController::class, 'index'])->name('index');
        Route::get('/{id}',            [StaffBookingController::class, 'show'])->name('show');
        Route::get('/{id}/invoice',    [StaffBookingController::class, 'invoice'])->name('invoice');
        Route::post('/{id}/confirm',   [StaffBookingController::class, 'confirm'])->name('confirm');
        Route::post('/{id}/cancel',    [StaffBookingController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/complete',  [StaffBookingController::class, 'complete'])->name('complete');
        Route::patch('/{id}/payment',  [StaffBookingController::class, 'updatePayment'])->name('update-payment');
        Route::get('/{id}/check-in',   [StaffBookingController::class, 'showCheckIn'])->name('check-in.show');
        Route::post('/{id}/check-in',  [StaffBookingController::class, 'checkIn'])->name('check-in');
        Route::post('/{id}/check-out', [StaffBookingController::class, 'checkOut'])->name('check-out');
    });

    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/',              [StaffPaymentController::class, 'index'])->name('index');
        Route::patch('/{id}/status', [StaffPaymentController::class, 'updateStatus'])->name('update-status');
    });

    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/',                 [StaffChatController::class, 'index'])->name('index');
        Route::get('/{customerId}',     [StaffChatController::class, 'show'])->name('show');
        Route::post('/{customerId}',    [StaffChatController::class, 'store'])->name('store')->middleware('throttle:30,1');
        Route::get('/{customerId}/poll', [StaffChatController::class, 'poll'])->name('poll');
    });
});
