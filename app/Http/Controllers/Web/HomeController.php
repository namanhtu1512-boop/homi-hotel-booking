<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\HotelService;
use App\Services\RoomTypeService;

class HomeController extends Controller
{
    public function __construct(
        private readonly HotelService $hotelService,
        private readonly RoomTypeService $roomTypeService,
    ) {}

    public function index()
    {
        $hotel = $this->hotelService->singleton();
        $featuredRoomTypes = $this->roomTypeService->list()->take(3);

        return view('home', compact('hotel', 'featuredRoomTypes'));
    }
}
