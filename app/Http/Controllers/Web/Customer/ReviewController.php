<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    public function create(): View
    {
        return view('customer.reviews.create', [
            'items' => $this->reviewService->reviewableItems(auth()->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_id'   => ['required', 'integer'],
            'room_type_id' => ['required', 'integer'],
            'rating'       => ['required', 'integer', 'between:1,5'],
            'comment'      => ['nullable', 'string', 'max:2000'],
            'images'       => ['nullable', 'array', 'max:5'],
            'images.*'     => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], [
            'booking_id'   => 'đơn đặt phòng',
            'room_type_id' => 'loại phòng',
            'rating'       => 'số sao',
            'comment'      => 'bình luận',
            'images.*'     => 'ảnh',
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
