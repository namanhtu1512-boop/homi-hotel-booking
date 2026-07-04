<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Services\HotelInfoService;
use Illuminate\View\View;

class HotelInfoController extends Controller
{
    public function __construct(private readonly HotelInfoService $hotelInfoService) {}

    public function show(): View
    {
        return view('staff.hotel-info.show', ['hotel' => $this->hotelInfoService->get()]);
    }
}
