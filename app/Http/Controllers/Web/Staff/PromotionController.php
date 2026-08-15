<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Services\PromotionService;
use Illuminate\View\View;

/**
 * Chỉ xem — nhân viên không tạo/sửa/xóa khuyến mãi trực tiếp, chỉ đề xuất
 * mã ưu đãi khách quen qua PromotionRequestController (xem
 * staff/group-discount-requests/index.blade.php).
 */
class PromotionController extends Controller
{
    public function __construct(
        private readonly PromotionService $promotionService,
    ) {}

    public function index(): View
    {
        return view('staff.promotions.index', ['promotions' => $this->promotionService->listVisible()]);
    }
}
