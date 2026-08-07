<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\FilterRoomRequest;
use App\Services\AvailabilityService;
use App\Services\HotelInfoService;
use App\Services\PricingService;
use App\Services\ReviewService;
use App\Services\RoomTypeService;
use App\Services\SeasonalRateService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function __construct(
        private readonly RoomTypeService $roomTypeService,
        private readonly AvailabilityService $availabilityService,
        private readonly HotelInfoService $hotelInfoService,
        private readonly ReviewService $reviewService,
        private readonly SeasonalRateService $seasonalRateService,
        private readonly PricingService $pricingService,
    ) {}

    public function index(FilterRoomRequest $request): View
    {
        $filters = array_filter([
            'keyword'   => $request->keyword(),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
            'capacity'  => $request->input('capacity'),
            'bed_type'  => $request->input('bed_type'),
            'sort'      => $request->input('sort'),
            'quantity'  => $request->input('quantity'),
            'check_in'  => $request->input('check_in'),
            'check_out' => $request->input('check_out'),
        ], fn ($value) => $value !== null && $value !== '');

        $roomTypes = $this->roomTypeService->search($filters);
        $roomTypeIds = $roomTypes->pluck('id')->all();

        // previewTonight() dùng chung PricingService::calculate() với lúc đặt
        // phòng thật, nên giá hiển thị ở đây không bao giờ lệch với giá thật
        // (gồm cả phụ thu cuối tuần). Chỉ đưa vào $discountedPrices khi
        // is_discount thật sự — 1 seasonal rate dương (phụ thu mùa cao điểm)
        // không được hiển thị như một chương trình giảm giá.
        $seasonalRates = [];
        $discountedPrices = [];
        $discountLabels = [];
        foreach ($roomTypes as $roomType) {
            $preview = $this->pricingService->previewTonight($roomType);

            if ($preview['is_discount'] && $preview['seasonal_rate']) {
                $seasonalRates[$roomType->id] = $preview['seasonal_rate'];
                $discountedPrices[$roomType->id] = $preview['preview_price'];
                $discountLabels[$roomType->id] = $this->seasonalRateService->shortDiscountLabel($preview['seasonal_rate']);
            }
        }

        return view('rooms.index', [
            'roomTypes' => $roomTypes,
            'filters'   => $request->only(['keyword', 'min_price', 'max_price', 'capacity', 'bed_type', 'sort', 'quantity', 'check_in', 'check_out']),
            'hotel'     => $this->hotelInfoService->current(),
            'ratings'   => $this->reviewService->summaryForMany($roomTypeIds),
            'seasonalRates'    => $seasonalRates,
            'discountedPrices' => $discountedPrices,
            'discountLabels'   => $discountLabels,
        ]);
    }

    public function show(int $id, Request $request): View
    {
        $roomType = $this->roomTypeService->findActive($id);

        $checkIn  = $request->query('check_in');
        $checkOut = $request->query('check_out');
        $quantity = max(1, (int) $request->query('quantity', 1));

        $availability = null;
        $availabilityError = null;

        if ($checkIn && $checkOut) {
            try {
                $availability = $this->availabilityService->check($id, $checkIn, $checkOut, $quantity);
            } catch (ValidationException $e) {
                $availabilityError = collect($e->errors())->flatten()->first();
            }
        }

        $relatedRooms = $this->roomTypeService->list()
            ->reject(fn ($room) => $room->id === $roomType->id)
            ->take(3);

        $preview = $this->pricingService->previewTonight($roomType);
        $seasonalRate = $preview['is_discount'] ? $preview['seasonal_rate'] : null;
        $discountedPrice = $seasonalRate ? $preview['preview_price'] : null;
        $discountLabel = $seasonalRate ? $this->seasonalRateService->shortDiscountLabel($seasonalRate) : null;

        return view('rooms.show', [
            'roomType'          => $roomType,
            'hotel'             => $this->hotelInfoService->current(),
            'availability'      => $availability,
            'availabilityError' => $availabilityError,
            'checkIn'           => $checkIn,
            'checkOut'          => $checkOut,
            'quantity'          => $quantity,
            'relatedRooms'      => $relatedRooms,
            'reviews'           => $this->reviewService->forRoomType($roomType->id),
            'reviewSummary'     => $this->reviewService->summaryFor($roomType->id),
            'seasonalRate'      => $seasonalRate,
            'discountedPrice'   => $discountedPrice,
            'discountLabel'     => $discountLabel,
            'amenityTiers'      => $this->roomTypeService->amenityTiers($roomType),
        ]);
    }
}
