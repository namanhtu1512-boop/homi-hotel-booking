<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PromotionService;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(private readonly PromotionService $promotionService) {}

    public function index(): View
    {
        return view('client.promotions', ['promotions' => $this->promotionService->activePublic()]);
    }
}
