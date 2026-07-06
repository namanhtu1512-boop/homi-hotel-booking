<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\GroupBookingRequest;
use App\Models\HotelInfo;
use App\Models\News;
use App\Models\Promotion;
use App\Models\Review;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\SeasonalRate;
use App\Models\Service;
use App\Models\User;
use App\Services\HotelInfoService;
use Illuminate\Database\Eloquent\Relations\Relation;
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
            'contact_messages' => ContactMessage::class,
            'seasonal_rates' => SeasonalRate::class,
            'services' => Service::class,
            'group_booking_requests' => GroupBookingRequest::class,
            'rooms' => Room::class,
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
