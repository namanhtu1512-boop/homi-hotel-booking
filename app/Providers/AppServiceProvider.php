<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Booking;
use App\Models\HotelInfo;
use App\Models\News;
use App\Models\Promotion;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\User;
use App\Services\HotelInfoService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton trong phạm vi request/test — tránh HotelInfoService::current()
        // bị resolve nhiều instance khác nhau (controller + footer composer) dẫn
        // tới query SELECT hotel_info lặp lại trên cùng 1 trang.
        $this->app->singleton(HotelInfoService::class);
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

        // Morph map: lưu alias ngắn gọn thay vì full class name trong cột polymorphic
        // (vd: audit_logs.auditable_type), tránh vỡ dữ liệu cũ khi đổi namespace.
        Relation::enforceMorphMap([
            'users'       => User::class,
            'hotel_info'  => HotelInfo::class,
            'room_types'  => RoomType::class,
            'bookings'    => Booking::class,
            'promotions'  => Promotion::class,
            'banners'     => Banner::class,
            'news'        => News::class,
            'reviews'     => Review::class,
        ]);

        // Footer hiển thị trên mọi trang khách hàng — chia sẻ thông tin khách sạn
        // singleton qua view composer thay vì mỗi controller phải tự truyền vào.
        // Dùng HotelInfoService::current() (singleton, có cache trong request)
        // thay vì gọi thẳng HotelInfo::instance() để không lặp lại query nếu
        // controller của trang đó đã tự tải hotel rồi.
        View::composer('partials._footer', function ($view) {
            $view->with('footerHotel', $this->app->make(HotelInfoService::class)->current());
        });
    }
}
