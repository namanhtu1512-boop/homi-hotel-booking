<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    public function create(Booking $booking): View|RedirectResponse
    {
        if (! $this->reviewService->canReview($booking, auth()->user())) {
            return redirect()
                ->route('customer.bookings.index')
                ->with('error', 'Đơn này chưa thể đánh giá hoặc đã được đánh giá rồi.');
        }

        return view('customer.reviews.create', [
            'booking' => $booking->load('bookingItems.roomType'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer'],
            'rating'     => ['required', 'integer', 'between:1,5'],
            'comment'    => ['nullable', 'string', 'max:2000'],
            'images'     => ['nullable', 'array', 'max:5'],
            'images.*'   => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], [
            'booking_id' => 'đơn đặt phòng',
            'rating'     => 'số sao đánh giá',
            'comment'    => 'bình luận',
            'images.*'   => 'ảnh',
        ]);

        $paths = [];
        foreach ($request->file('images', []) as $file) {
            $paths[] = $file->store('reviews', 'public');
        }
        $data['images'] = $paths ?: null;

        $this->reviewService->create($request->user(), $data);

        return redirect()
            ->route('customer.bookings.index')
            ->with('success', 'Cảm ơn bạn đã đánh giá! Đánh giá của bạn đã được đăng công khai.');
    }
}
