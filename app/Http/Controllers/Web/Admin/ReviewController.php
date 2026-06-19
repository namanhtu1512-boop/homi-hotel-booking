<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Review;
use App\Services\AuditLogService;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $reviews = $this->reviewService->adminList(
            filters: $request->only(['hotel_id', 'rating', 'visible']),
            perPage: 15,
        )->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'hotels'  => Hotel::orderBy('name')->get(),
            'hotelId' => $request->input('hotel_id', ''),
            'rating'  => $request->input('rating', ''),
            'visible' => $request->input('visible', ''),
        ]);
    }

    public function toggleVisibility(Review $review): RedirectResponse
    {
        $review = $this->reviewService->toggleVisibility($review);

        $this->auditLog->log(
            'review.visibility_toggled',
            $review,
            ($review->is_visible ? 'Hiện lại' : 'Ẩn') . " đánh giá #{$review->id} của \"{$review->user->name}\"."
        );

        return redirect()
            ->back()
            ->with('success', $review->is_visible ? 'Đã hiện lại đánh giá.' : 'Đã ẩn đánh giá.');
    }
}
