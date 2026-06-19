<?php

namespace App\Providers;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Gate: chỉ admin
        Gate::define('admin-only', fn(User $user) => $user->role === 'admin');

        // Gate: admin hoặc staff
        Gate::define('admin-or-staff', fn(User $user) => in_array($user->role, ['admin', 'staff']));

        // Gate: chỉ customer
        Gate::define('customer-only', fn(User $user) => $user->role === 'customer');

        // Gate: tài khoản đang hoạt động (dùng để kiểm tra trước khi thực hiện thao tác nhạy cảm)
        Gate::define('active-account', fn(User $user) => $user->status === 'active');

        // Sidebar admin cần số đơn chờ duyệt ở mọi trang — chia sẻ qua view composer
        // thay vì lặp lại query này trong từng controller.
        View::composer('layouts.admin', function ($view) {
            $view->with('pendingBookingsCount', Booking::where('status', 'pending')->count());
        });

        // Morph map: lưu alias ngắn gọn thay vì full class name trong cột polymorphic
        // (vd: audit_logs.auditable_type), tránh vỡ dữ liệu cũ khi đổi namespace.
        Relation::enforceMorphMap([
            'users'      => User::class,
            'hotels'     => Hotel::class,
            'room_types' => RoomType::class,
            'bookings'   => Booking::class,
            'amenities'  => Amenity::class,
            'promotions' => Promotion::class,
            'reviews'    => Review::class,
            'payments'   => Payment::class,
        ]);
    }
}
