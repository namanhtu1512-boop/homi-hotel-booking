<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\FilterRoomRequest;
use App\Services\AvailabilityService;
use App\Services\HotelInfoService;
use App\Services\ReviewService;
use App\Services\RoomTypeService;
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

        return view('rooms.index', [
            'roomTypes' => $roomTypes,
            'filters'   => $request->only(['keyword', 'min_price', 'max_price', 'capacity', 'bed_type', 'sort', 'quantity', 'check_in', 'check_out']),
            'hotel'     => $this->hotelInfoService->current(),
            'ratings'   => $this->reviewService->summaryForMany($roomTypes->pluck('id')->all()),
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
        ]);
    }
}
