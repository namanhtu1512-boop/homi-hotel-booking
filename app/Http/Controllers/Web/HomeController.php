<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\FilterRoomTypeRequest;
use App\Services\HotelInfoService;
use App\Services\RoomTypeService;

class HomeController extends Controller
{
    public function __construct(
        private readonly HotelInfoService $hotelInfoService,
        private readonly RoomTypeService $roomTypeService,
    ) {}

    public function index(FilterRoomTypeRequest $request)
    {
        $hotel     = $this->hotelInfoService->get();
        $filters   = $request->filters();
        $roomTypes = $this->roomTypeService->search($filters, 12);

        return view('client.home', compact('hotel', 'roomTypes', 'filters'));
    }
}
