<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\FilterRoomRequest;
use App\Services\AvailabilityService;
use App\Services\RoomTypeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function __construct(
        private readonly RoomTypeService $roomTypeService,
        private readonly AvailabilityService $availabilityService,
    ) {}

    public function index(FilterRoomRequest $request): View
    {
        $filters = array_filter([
            'keyword'   => $request->keyword(),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
            'capacity'  => $request->input('capacity'),
        ], fn ($value) => $value !== null && $value !== '');

        return view('rooms.index', [
            'roomTypes' => $this->roomTypeService->search($filters),
            'filters'   => $request->only(['keyword', 'min_price', 'max_price', 'capacity', 'check_in', 'check_out']),
        ]);
    }

    public function show(int $id, Request $request): View
    {
        $roomType = $this->roomTypeService->findActive($id);

        $checkIn  = $request->query('check_in');
        $checkOut = $request->query('check_out');
        $quantity = max(1, (int) $request->query('quantity', 1));

        $availability = null;
        $availabilityError = null;

        if ($checkIn && $checkOut) {
            try {
                $availability = $this->availabilityService->check($id, $checkIn, $checkOut, $quantity);
            } catch (ValidationException $e) {
                $availabilityError = collect($e->errors())->flatten()->first();
            }
        }

        return view('rooms.show', [
            'roomType'          => $roomType,
            'availability'      => $availability,
            'availabilityError' => $availabilityError,
            'checkIn'           => $checkIn,
            'checkOut'          => $checkOut,
            'quantity'          => $quantity,
        ]);
    }
}
