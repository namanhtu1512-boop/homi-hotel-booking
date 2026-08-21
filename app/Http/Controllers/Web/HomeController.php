<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\FilterRoomTypeRequest;
use App\Services\BannerService;
use App\Services\HotelInfoService;
use App\Services\NewsService;
use App\Services\PromotionService;
use App\Services\ReviewService;
use App\Services\RoomCombinationService;
use App\Services\RoomTypeService;
use App\Services\SeasonalRateService;
use Illuminate\Support\Collection;

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
        private readonly RoomCombinationService $roomCombinationService,
    ) {}

    public function index(FilterRoomTypeRequest $request)
    {
        $hotel      = $this->hotelInfoService->get();
        $filters    = $request->filters();
        $candidates = $this->roomTypeService->searchCandidates($filters);
        $roomTypes  = $this->roomTypeService->paginate($candidates, 12);
        $isSearching = ! empty(array_filter($filters));
        $combination = $this->buildCombination($filters, $candidates);

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
            'hotel', 'roomTypes', 'filters', 'isSearching', 'featuredRooms', 'promotions', 'reviews', 'banners', 'news', 'seasonalRates', 'combination'
        ));
    }

    /**
     * Chạy tổ hợp phòng cho ô tìm nhanh trên trang chủ khi khách đã nhập đủ
     * ngày + số phòng + số khách — tái sử dụng đúng $candidates vừa lọc,
     * không query lại. Không có bộ lọc category/amenities ở trang chủ nên
     * không cần gợi ý category thay thế như RoomController.
     */
    private function buildCombination(array $filters, Collection $candidates): ?array
    {
        if (empty($filters['check_in']) || empty($filters['check_out']) || empty($filters['guests'])) {
            return null;
        }

        $quantity = max(1, (int) ($filters['quantity'] ?? 1));
        $guests   = (int) $filters['guests'];

        $combinationCandidates = $candidates->map(fn ($rt) => [
            'room_type_id'        => $rt->id,
            'name'                => $rt->name,
            'category'            => $rt->category,
            'capacity'            => (int) $rt->capacity,
            'available_quantity'  => (int) $rt->available_quantity,
            'price_per_night'     => (float) $rt->price_per_night,
        ])->values();

        return $this->roomCombinationService->find($combinationCandidates, $quantity, $guests);
    }
}
