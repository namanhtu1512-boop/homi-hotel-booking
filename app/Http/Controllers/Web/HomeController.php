<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\FilterRoomTypeRequest;
use App\Services\BannerService;
use App\Services\HotelInfoService;
use App\Services\NewsService;
use App\Services\PromotionService;
use App\Services\ReviewService;
use App\Services\RoomTypeService;
use App\Services\SeasonalRateService;

class HomeController extends Controller
{
    public function __construct(
        private readonly HotelInfoService $hotelInfoService,
        private readonly RoomTypeService $roomTypeService,
        private readonly PromotionService $promotionService,
        private readonly ReviewService $reviewService,
        private readonly BannerService $bannerService,
        private readonly NewsService $newsService,
        private readonly SeasonalRateService $seasonalRateService,
    ) {}

    public function index(FilterRoomTypeRequest $request)
    {
        $hotel     = $this->hotelInfoService->get();
        $filters   = $request->filters();
        $roomTypes = $this->roomTypeService->search($filters, 12);
        $isSearching = ! empty(array_filter($filters));

        $featuredRooms = $this->roomTypeService->featured(6);
        $promotions    = $this->promotionService->activePublic()->take(3);
        $reviews       = $this->reviewService->latestVisible(6);
        $banners       = $this->bannerService->activeOrdered();
        $news          = $this->newsService->latestPublished(3);

        $seasonalRates = $this->seasonalRateService->activeForDate(
            $featuredRooms->pluck('id')->merge($roomTypes->pluck('id'))->unique()->all(),
            $filters['check_in'] ?? null
        );

        return view('client.home', compact(
            'hotel', 'roomTypes', 'filters', 'isSearching', 'featuredRooms', 'promotions', 'reviews', 'banners', 'news', 'seasonalRates'
        ));
    }
}
