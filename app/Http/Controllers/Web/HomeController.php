<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\HotelService;

class HomeController extends Controller
{
    public function __construct(private readonly HotelService $hotelService) {}

    public function index()
    {
        $hotel = $this->hotelService->singleton();

        return view('home', compact('hotel'));
    }
}
