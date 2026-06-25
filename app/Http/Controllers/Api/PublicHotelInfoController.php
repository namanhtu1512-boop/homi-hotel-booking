<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HotelInfoService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Trang giới thiệu khách sạn (public) — chỉ có 1 endpoint xem thông tin,
 * không có list/search vì hệ thống chỉ có đúng 1 khách sạn.
 */
class PublicHotelInfoController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly HotelInfoService $hotelInfoService) {}

    public function show(): JsonResponse
    {
        return $this->success($this->hotelInfoService->get());
    }
}
