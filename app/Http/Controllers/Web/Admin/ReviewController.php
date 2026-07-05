<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
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
        return view('admin.reviews.index', [
            'reviews' => $this->reviewService->adminList($request->only('status')),
            'filters' => $request->only('status'),
        ]);
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $review = Review::findOrFail($id);

        $updated = $this->reviewService->toggleStatus($review);

        $this->auditLog->log('review.status_toggled', $updated, "Đổi trạng thái đánh giá #{$updated->id} thành \"{$updated->status}\".");

        return redirect()->route('admin.reviews.index')->with('success', 'Đã cập nhật trạng thái đánh giá.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $review = Review::findOrFail($id);

        $this->reviewService->delete($review);

        $this->auditLog->log('review.deleted', null, "Xóa đánh giá #{$id}.");

        return redirect()->route('admin.reviews.index')->with('success', 'Đã xóa đánh giá.');
    }
}
