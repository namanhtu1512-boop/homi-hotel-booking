<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\SocialAuthController;
use App\Http\Controllers\Web\PasswordResetController;
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
use App\Http\Controllers\Web\Admin\GroupDiscountPolicyController as AdminGroupDiscountPolicyController;
use App\Http\Controllers\Web\Admin\GroupDiscountRequestController as AdminGroupDiscountRequestController;
use App\Http\Controllers\Web\Admin\PromotionRequestController as AdminPromotionRequestController;
use App\Http\Controllers\Web\Staff\GroupDiscountRequestController as StaffGroupDiscountRequestController;
use App\Http\Controllers\Web\Staff\PromotionRequestController as StaffPromotionRequestController;
use App\Http\Controllers\Web\Staff\PromotionController as StaffPromotionController;
use App\Http\Controllers\Web\Admin\SeasonalRateController as AdminSeasonalRateController;
use App\Http\Controllers\Web\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Web\Admin\SurchargeItemController as AdminSurchargeItemController;
use App\Http\Controllers\Web\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Web\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Web\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Web\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Web\Admin\AuditLogController as AdminAuditLogController;
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
use App\Http\Controllers\Web\Customer\RoomChangeRequestController as CustomerRoomChangeRequestController;
use App\Http\Controllers\Web\Admin\RoomChangeRequestController as AdminRoomChangeRequestController;
use App\Http\Controllers\Web\Staff\RoomChangeRequestController as StaffRoomChangeRequestController;
use App\Http\Controllers\Web\Customer\ExtraBedRequestController as CustomerExtraBedRequestController;
use App\Http\Controllers\Web\Admin\ExtraBedRequestController as AdminExtraBedRequestController;
use App\Http\Controllers\Web\Staff\ExtraBedRequestController as StaffExtraBedRequestController;
use App\Http\Controllers\Web\Customer\LateCheckoutRequestController as CustomerLateCheckoutRequestController;
use App\Http\Controllers\Web\Admin\LateCheckoutRequestController as AdminLateCheckoutRequestController;
use App\Http\Controllers\Web\Staff\LateCheckoutRequestController as StaffLateCheckoutRequestController;
use App\Http\Controllers\Web\Customer\ChatController as CustomerChatController;
use App\Http\Controllers\Web\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Web\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Web\Staff\HotelInfoController as StaffHotelInfoController;
use App\Http\Controllers\Web\Staff\RoomTypeController as StaffRoomTypeController;
use App\Http\Controllers\Web\Staff\RoomController as StaffRoomController;
use App\Http\Controllers\Web\Staff\BookingController as StaffBookingController;
use App\Http\Controllers\Web\Staff\PaymentController as StaffPaymentController;
use App\Http\Controllers\Web\Staff\ChatController as StaffChatController;
use App\Http\Controllers\Web\Staff\GroupBookingController as StaffGroupBookingController;
use App\Http\Controllers\Web\PaymentGatewayController;
use App\Http\Controllers\Web\AboutController;
use App\Http\Controllers\Web\NotificationPollController;
use App\Http\Controllers\Web\Customer\NotificationController as CustomerNotificationController;
use App\Http\Controllers\Web\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Web\AiAssistantController;

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
Route::post('/group-bookings/suggest-options', [GroupBookingController::class, 'suggestOptions'])->middleware('throttle:20,1')->name('group-bookings.suggest-options');
Route::post('/group-bookings/extra-bed-availability', [GroupBookingController::class, 'extraBedAvailability'])->middleware('throttle:30,1')->name('group-bookings.extra-bed-availability');
Route::post('/ai-assistant/chat', [AiAssistantController::class, 'chat'])->middleware('throttle:20,1')->name('ai-assistant.chat');
Route::post('/group-bookings', [GroupBookingController::class, 'store'])->middleware(['auth', 'role:customer', 'throttle:5,1'])->name('group-bookings.store');

// Notification polling — dùng chung cho mọi role đã đăng nhập
Route::middleware('auth')->group(function () {
    Route::get('/notifications/poll', [NotificationPollController::class, 'poll'])->name('notifications.poll');
    Route::post('/notifications/read', [NotificationPollController::class, 'markRead'])->name('notifications.read.ajax');
});

// Health-check (Week 1 BE1)
Route::get('/health', fn () => response()->json(['status' => 'ok', 'timestamp' => now()->toISOString()]))->name('health');

// VNPay gọi về sau khi khách thanh toán — công khai, không qua middleware
// auth (VNPay không mang session/cookie của khách khi redirect/gọi IPN).
Route::prefix('payment/vnpay')->name('payment.vnpay.')->group(function () {
    Route::get('/return', [PaymentGatewayController::class, 'vnpayReturn'])->name('return');
    // VNPay gọi IPN bằng GET (query string), không phải POST — vẫn thêm cả
    // POST để tương thích nếu cấu hình sandbox dùng phương thức khác.
    Route::match(['get', 'post'], '/ipn', [PaymentGatewayController::class, 'vnpayIpn'])->name('ipn');
});

// ---------------------------------------------------------------
// Auth — guest only
// ---------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/customer/register', [AuthWebController::class, 'showRegister'])->name('register');
    Route::post('/customer/register', [AuthWebController::class, 'register'])->middleware('throttle:5,1');

    Route::get('/customer/login', [AuthWebController::class, 'showLogin'])->name('login');
    Route::post('/customer/login', [AuthWebController::class, 'login'])->middleware('throttle:5,1');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('/logout', [AuthWebController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Đăng nhập Facebook/Google — không đặt trong 'guest' group vì callback vẫn
// cần xử lý được (redirect có thông báo lỗi) ngay cả khi phiên guest đã hết.
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['facebook', 'google'])
    ->middleware('throttle:10,1')
    ->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['facebook', 'google'])
    ->name('social.callback');

// Admin login (separate, not under guest middleware so already-logged-in admins get redirected)
Route::get('/admin/login', [AuthWebController::class, 'showAdminLogin'])->name('admin.login');
Route::post('/admin/login', [AuthWebController::class, 'adminLogin'])->middleware('throttle:5,1')->name('admin.login.post');

Route::post('/admin/logout', [AuthWebController::class, 'adminLogout'])
    ->middleware('auth')
    ->name('admin.logout');

// Staff login — cổng riêng, tách biệt hoàn toàn với admin (route/view/session
// context riêng, xem RoleMiddleware::loginRouteFor()).
Route::get('/staff/login', [AuthWebController::class, 'showStaffLogin'])->name('staff.login');
Route::post('/staff/login', [AuthWebController::class, 'staffLogin'])->middleware('throttle:5,1')->name('staff.login.post');

Route::post('/staff/logout', [AuthWebController::class, 'staffLogout'])
    ->middleware('auth')
    ->name('staff.logout');

// ---------------------------------------------------------------
// CUSTOMER — authenticated customers
// ---------------------------------------------------------------
Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::post('/notifications/read', [CustomerNotificationController::class, 'markRead'])->name('notifications.read');

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
        // (xem Booking::canMarkPaymentAsPaid()). pay-online redirect sang VNPay
        // thật, trạng thái chỉ đổi khi VNPay xác nhận qua return/IPN.
        Route::post('/{id}/pay/online',        [CustomerBookingController::class, 'payOnline'])->name('pay-online');
        Route::post('/{id}/pay/bank-transfer', [CustomerBookingController::class, 'payBankTransfer'])->name('pay-bank-transfer');
        Route::post('/{id}/pay/deposit',       [CustomerBookingController::class, 'payDeposit'])->name('pay-deposit');
        Route::post('/{id}/pay/cancel-vnpay',  [CustomerBookingController::class, 'cancelVnpay'])->name('pay-cancel-vnpay');

        Route::get('/{id}/room-change',  [CustomerRoomChangeRequestController::class, 'create'])->name('room-change.create');
        Route::post('/{id}/room-change', [CustomerRoomChangeRequestController::class, 'store'])->name('room-change.store');

        // Yêu cầu giường phụ không tự khách "tạo" (tự sinh ra khi đặt phòng
        // thiếu giường phụ, xem BookingService::create()) — chỉ cần 1 action
        // để khách phản hồi phương án đã được gợi ý.
        Route::post('/{id}/extra-bed/resolve', [CustomerExtraBedRequestController::class, 'resolve'])->name('extra-bed.resolve');

        Route::get('/{id}/late-checkout',  [CustomerLateCheckoutRequestController::class, 'create'])->name('late-checkout.create');
        Route::post('/{id}/late-checkout', [CustomerLateCheckoutRequestController::class, 'store'])->name('late-checkout.store');
    });

    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/',            [CustomerWishlistController::class, 'index'])->name('index');
        Route::post('/{roomType}', [CustomerWishlistController::class, 'store'])->name('store');
        Route::patch('/{item}',    [CustomerWishlistController::class, 'update'])->name('update');
        Route::delete('/{item}',   [CustomerWishlistController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/create/{booking}', [CustomerReviewController::class, 'create'])->name('create');
        Route::post('/',                [CustomerReviewController::class, 'store'])->name('store');
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
    Route::post('/notifications/read', [AdminNotificationController::class, 'markRead'])->name('notifications.read');

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
        Route::patch('/{id}/toggle-status', [RoomTypeController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{id}',        [RoomTypeController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore',  [RoomTypeController::class, 'restore'])->name('restore');
    });

    Route::prefix('rooms')->name('rooms.')->group(function () {
        Route::get('/',                       [AdminRoomController::class, 'index'])->name('index');
        Route::get('/calendar',               [AdminRoomController::class, 'calendar'])->name('calendar');
        Route::get('/create',                 [AdminRoomController::class, 'create'])->name('create');
        Route::post('/',                      [AdminRoomController::class, 'store'])->name('store');
        Route::get('/{id}/edit',              [AdminRoomController::class, 'edit'])->name('edit');
        Route::get('/{id}',                   [AdminRoomController::class, 'show'])->name('show');
        Route::put('/{id}',                   [AdminRoomController::class, 'update'])->name('update');
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
        Route::get('/create',          [AdminBookingController::class, 'create'])->name('create');
        Route::post('/',               [AdminBookingController::class, 'store'])->name('store');
        Route::get('/{id}',            [AdminBookingController::class, 'show'])->name('show');
        Route::get('/{id}/invoice',    [AdminBookingController::class, 'invoice'])->name('invoice');
        Route::post('/{id}/confirm',   [AdminBookingController::class, 'confirm'])->name('confirm');
        Route::post('/{id}/cancel',    [AdminBookingController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/complete',  [AdminBookingController::class, 'complete'])->name('complete');
        Route::patch('/{id}/payment',  [AdminBookingController::class, 'updatePayment'])->name('update-payment');
        Route::get('/{id}/check-in',   [AdminBookingController::class, 'showCheckIn'])->name('check-in.show');
        Route::post('/{id}/check-in',  [AdminBookingController::class, 'checkIn'])->name('check-in');
        Route::get('/{id}/check-out',  [AdminBookingController::class, 'showCheckOut'])->name('check-out.show');
        Route::post('/{id}/check-out', [AdminBookingController::class, 'checkOut'])->name('check-out');

        // Phát sinh trong lúc lưu trú (chỉ áp dụng khi đơn đã check-in — xem
        // BookingService::addServiceItem()/addSurcharge()).
        Route::post('/{id}/services',  [AdminBookingController::class, 'addService'])->name('services.store');
        Route::post('/{id}/surcharge', [AdminBookingController::class, 'addSurcharge'])->name('surcharge.store');

        Route::get('/{id}/extend-stay/preview', [AdminBookingController::class, 'previewExtendStay'])->name('extend-stay.preview');
        Route::post('/{id}/extend-stay',         [AdminBookingController::class, 'extendStay'])->name('extend-stay.store');

        // Ưu đãi đoàn/nhóm — admin áp trực tiếp, không bị trần cấu hình (xem
        // GroupDiscountRequestController@store).
        Route::post('/{id}/group-discount', [AdminGroupDiscountRequestController::class, 'store'])->name('group-discount.store');
    });

    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/',               [AdminPaymentController::class, 'index'])->name('index');
        Route::patch('/{id}/status',  [AdminPaymentController::class, 'updateStatus'])->name('update-status');
    });

    Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [AdminAuditLogController::class, 'index'])->name('index');
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

    Route::prefix('group-discount-policies')->name('group-discount-policies.')->group(function () {
        Route::get('/',               [AdminGroupDiscountPolicyController::class, 'index'])->name('index');
        Route::get('/create',         [AdminGroupDiscountPolicyController::class, 'create'])->name('create');
        Route::post('/',              [AdminGroupDiscountPolicyController::class, 'store'])->name('store');
        Route::get('/{id}/edit',      [AdminGroupDiscountPolicyController::class, 'edit'])->name('edit');
        Route::put('/{id}',           [AdminGroupDiscountPolicyController::class, 'update'])->name('update');
        Route::delete('/{id}',        [AdminGroupDiscountPolicyController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore',  [AdminGroupDiscountPolicyController::class, 'restore'])->name('restore');
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

    Route::prefix('surcharge-items')->name('surcharge-items.')->group(function () {
        Route::get('/',               [AdminSurchargeItemController::class, 'index'])->name('index');
        Route::get('/create',         [AdminSurchargeItemController::class, 'create'])->name('create');
        Route::post('/',              [AdminSurchargeItemController::class, 'store'])->name('store');
        Route::get('/{id}/edit',      [AdminSurchargeItemController::class, 'edit'])->name('edit');
        Route::put('/{id}',           [AdminSurchargeItemController::class, 'update'])->name('update');
        Route::delete('/{id}',        [AdminSurchargeItemController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore',  [AdminSurchargeItemController::class, 'restore'])->name('restore');
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
        Route::get('/',                      [AdminGroupBookingController::class, 'index'])->name('index');
        Route::get('/{id}',                  [AdminGroupBookingController::class, 'show'])->name('show');
        Route::post('/{id}/create-booking',  [AdminGroupBookingController::class, 'createBooking'])->name('create-booking');
        Route::patch('/{id}/mark-contacted', [AdminGroupBookingController::class, 'markContacted'])->name('mark-contacted');
        Route::post('/{id}/send-quote',      [AdminGroupBookingController::class, 'sendQuote'])->name('send-quote');
        Route::delete('/{id}',               [AdminGroupBookingController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('room-change-requests')->name('room-change-requests.')->group(function () {
        Route::get('/',              [AdminRoomChangeRequestController::class, 'index'])->name('index');
        Route::get('/{id}',          [AdminRoomChangeRequestController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [AdminRoomChangeRequestController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject',  [AdminRoomChangeRequestController::class, 'reject'])->name('reject');
    });

    Route::prefix('extra-bed-requests')->name('extra-bed-requests.')->group(function () {
        Route::get('/',              [AdminExtraBedRequestController::class, 'index'])->name('index');
        Route::get('/{id}',          [AdminExtraBedRequestController::class, 'show'])->name('show');
        Route::post('/{id}/resolve', [AdminExtraBedRequestController::class, 'resolve'])->name('resolve');
    });

    Route::prefix('late-checkout-requests')->name('late-checkout-requests.')->group(function () {
        Route::get('/',              [AdminLateCheckoutRequestController::class, 'index'])->name('index');
        Route::get('/{id}',          [AdminLateCheckoutRequestController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [AdminLateCheckoutRequestController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject',  [AdminLateCheckoutRequestController::class, 'reject'])->name('reject');
    });

    Route::prefix('group-discount-requests')->name('group-discount-requests.')->group(function () {
        Route::get('/',              [AdminGroupDiscountRequestController::class, 'index'])->name('index');
        Route::get('/{id}',          [AdminGroupDiscountRequestController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [AdminGroupDiscountRequestController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject',  [AdminGroupDiscountRequestController::class, 'reject'])->name('reject');
        Route::post('/{id}/adjust',  [AdminGroupDiscountRequestController::class, 'adjust'])->name('adjust');
    });

    // Nhân viên đề xuất mã ưu đãi đoàn cho khách quen — duyệt/từ chối ngay
    // trên trang Khuyến mãi (xem admin/promotions/index.blade.php).
    Route::prefix('promotion-requests')->name('promotion-requests.')->group(function () {
        Route::post('/{id}/approve', [AdminPromotionRequestController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject',  [AdminPromotionRequestController::class, 'reject'])->name('reject');
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
        Route::get('/',      [StaffRoomTypeController::class, 'index'])->name('index');
        Route::get('/{id}',  [StaffRoomTypeController::class, 'show'])->name('show')->whereNumber('id');
        Route::patch('/{id}/toggle-status', [StaffRoomTypeController::class, 'toggleStatus'])->name('toggle-status')->whereNumber('id');
    });

    Route::prefix('rooms')->name('rooms.')->group(function () {
        Route::get('/',                    [StaffRoomController::class, 'index'])->name('index');
        Route::get('/calendar',            [StaffRoomController::class, 'calendar'])->name('calendar');
        Route::get('/{id}',                [StaffRoomController::class, 'show'])->name('show');
        Route::patch('/{id}/housekeeping', [StaffRoomController::class, 'updateHousekeeping'])->name('update-housekeeping');
    });

    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/',                [StaffBookingController::class, 'index'])->name('index');
        Route::get('/create',          [StaffBookingController::class, 'create'])->name('create');
        Route::post('/',               [StaffBookingController::class, 'store'])->name('store');
        Route::get('/{id}',            [StaffBookingController::class, 'show'])->name('show');
        Route::get('/{id}/invoice',    [StaffBookingController::class, 'invoice'])->name('invoice');
        Route::post('/{id}/confirm',   [StaffBookingController::class, 'confirm'])->name('confirm');
        Route::post('/{id}/cancel',    [StaffBookingController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/complete',  [StaffBookingController::class, 'complete'])->name('complete');
        Route::patch('/{id}/payment',  [StaffBookingController::class, 'updatePayment'])->name('update-payment');
        Route::get('/{id}/check-in',   [StaffBookingController::class, 'showCheckIn'])->name('check-in.show');
        Route::post('/{id}/check-in',  [StaffBookingController::class, 'checkIn'])->name('check-in');
        Route::get('/{id}/check-out',  [StaffBookingController::class, 'showCheckOut'])->name('check-out.show');
        Route::post('/{id}/check-out', [StaffBookingController::class, 'checkOut'])->name('check-out');

        Route::post('/{id}/services',  [StaffBookingController::class, 'addService'])->name('services.store');
        Route::post('/{id}/surcharge', [StaffBookingController::class, 'addSurcharge'])->name('surcharge.store');

        Route::get('/{id}/extend-stay/preview', [StaffBookingController::class, 'previewExtendStay'])->name('extend-stay.preview');
        Route::post('/{id}/extend-stay',         [StaffBookingController::class, 'extendStay'])->name('extend-stay.store');

        Route::post('/{id}/group-discount', [StaffGroupDiscountRequestController::class, 'store'])->name('group-discount.store');
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

    Route::prefix('group-bookings')->name('group-bookings.')->group(function () {
        Route::get('/',                      [StaffGroupBookingController::class, 'index'])->name('index');
        Route::get('/{id}',                  [StaffGroupBookingController::class, 'show'])->name('show');
        Route::post('/{id}/create-booking',  [StaffGroupBookingController::class, 'createBooking'])->name('create-booking');
        Route::patch('/{id}/mark-contacted', [StaffGroupBookingController::class, 'markContacted'])->name('mark-contacted');
        Route::post('/{id}/send-quote',      [StaffGroupBookingController::class, 'sendQuote'])->name('send-quote');
    });

    Route::prefix('room-change-requests')->name('room-change-requests.')->group(function () {
        Route::get('/',              [StaffRoomChangeRequestController::class, 'index'])->name('index');
        Route::get('/{id}',          [StaffRoomChangeRequestController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [StaffRoomChangeRequestController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject',  [StaffRoomChangeRequestController::class, 'reject'])->name('reject');
    });

    Route::prefix('extra-bed-requests')->name('extra-bed-requests.')->group(function () {
        Route::get('/',              [StaffExtraBedRequestController::class, 'index'])->name('index');
        Route::get('/{id}',          [StaffExtraBedRequestController::class, 'show'])->name('show');
        Route::post('/{id}/resolve', [StaffExtraBedRequestController::class, 'resolve'])->name('resolve');
    });

    Route::prefix('late-checkout-requests')->name('late-checkout-requests.')->group(function () {
        Route::get('/',              [StaffLateCheckoutRequestController::class, 'index'])->name('index');
        Route::get('/{id}',          [StaffLateCheckoutRequestController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [StaffLateCheckoutRequestController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject',  [StaffLateCheckoutRequestController::class, 'reject'])->name('reject');
    });

    // Chỉ xem lịch sử đề xuất CỦA CHÍNH MÌNH — duyệt/từ chối/điều chỉnh chỉ
    // admin làm được (xem StaffGroupDiscountRequestController). Trang này còn
    // hiển thị form tạo đề xuất mã ưu đãi khách quen (PromotionRequestController).
    Route::prefix('group-discount-requests')->name('group-discount-requests.')->group(function () {
        Route::get('/',     [StaffGroupDiscountRequestController::class, 'index'])->name('index');
        Route::get('/{id}', [StaffGroupDiscountRequestController::class, 'show'])->name('show');
    });

    Route::post('/promotion-requests', [StaffPromotionRequestController::class, 'store'])->name('promotion-requests.store');

    // Chỉ xem — không tạo/sửa/xóa được, xem group-discount-requests.index để
    // đề xuất mã mới.
    Route::get('/promotions', [StaffPromotionController::class, 'index'])->name('promotions.index');
});
