<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\FilterRoomTypeRequest;
use App\Services\BannerService;
use App\Services\HotelInfoService;
use App\Services\PromotionService;
use App\Services\ReviewService;
use App\Services\RoomTypeService;

class HomeController extends Controller
{
    public function __construct(
        private readonly HotelInfoService $hotelInfoService,
        private readonly RoomTypeService $roomTypeService,
        private readonly PromotionService $promotionService,
        private readonly ReviewService $reviewService,
        private readonly BannerService $bannerService,
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

        return view('client.home', compact(
            'hotel', 'roomTypes', 'filters', 'isSearching', 'featuredRooms', 'promotions', 'reviews', 'banners'
        ));
    }
}
