<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HotelService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicHotelController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly HotelService $hotelService) {}

    public function index(Request $request): JsonResponse
    {
        $hotels = $this->hotelService->publicList(
            filters: $request->only(['keyword', 'city', 'amenities', 'min_price', 'max_price']),
            perPage: (int) $request->input('per_page', 10),
        );

        return $this->paginated($hotels, 'hotels');
    }

    public function show(int $id): JsonResponse
    {
        $hotel = $this->hotelService->publicFind($id);

        return $this->success($hotel);
    }
}
