<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\HotelInfoService;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function __construct(private readonly HotelInfoService $hotelInfoService) {}

    public function index(): View
    {
        $hotel = $this->hotelInfoService->get();

        return view('client.about', compact('hotel'));
    }
}
