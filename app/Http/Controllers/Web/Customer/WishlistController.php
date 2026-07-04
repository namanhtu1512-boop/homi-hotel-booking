<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wishlist\StoreWishlistItemRequest;
use App\Http\Requests\Wishlist\UpdateWishlistItemRequest;
use App\Services\WishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function __construct(
        private readonly WishlistService $wishlistService,
    ) {}

    public function index(Request $request): View
    {
        return view('customer.wishlist.index', [
            'items' => $this->wishlistService->list($request->user()),
        ]);
    }

    public function store(StoreWishlistItemRequest $request, int $roomType): RedirectResponse
    {
        $item = $this->wishlistService->add(
            $request->user(),
            $roomType,
            (int) ($request->validated('quantity') ?? 1),
        );

        return back()->with('success', "Đã thêm \"{$item->roomType->name}\" vào danh sách chờ.");
    }

    public function update(UpdateWishlistItemRequest $request, int $item): RedirectResponse
    {
        $this->wishlistService->updateQuantity($request->user(), $item, (int) $request->validated('quantity'));
        $this->wishlistService->updateGuests(
            $request->user(),
            $item,
            (int) $request->validated('adults'),
            (int) ($request->validated('children') ?? 0),
        );

        return back()->with('success', 'Đã cập nhật danh sách chờ.');
    }

    public function destroy(Request $request, int $item): RedirectResponse
    {
        $this->wishlistService->remove($request->user(), $item);

        return back()->with('success', 'Đã xóa khỏi danh sách chờ.');
    }
}
