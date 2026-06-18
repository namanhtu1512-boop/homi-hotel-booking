<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Hotel;
use App\Services\HotelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HotelController extends Controller
{
    public function __construct(private readonly HotelService $hotelService) {}

    public function index(Request $request): View
    {
        $hotels = $this->hotelService->adminList(
            filters: $request->only(['search', 'status', 'sort_by', 'sort_order']),
            perPage: 10,
        );

        return view('admin.hotels.index', [
            'hotels' => $hotels,
            'search' => $request->input('search', ''),
            'status' => $request->input('status', ''),
        ]);
    }

    public function show(int $id): View
    {
        $hotel = $this->hotelService->adminFind($id);

        return view('admin.hotels.show', ['hotel' => $hotel]);
    }

    public function create(): View
    {
        return view('admin.hotels.form', [
            'hotel'     => null,
            'amenities' => Amenity::orderBy('name')->get(),
            'selectedAmenityIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateHotel($request);

        $hotel = $this->hotelService->create($data);

        return redirect()
            ->route('admin.hotels.index')
            ->with('success', "Đã tạo khách sạn \"{$hotel->name}\".");
    }

    public function edit(int $id): View
    {
        $hotel = $this->hotelService->adminFind($id);

        return view('admin.hotels.form', [
            'hotel'              => $hotel,
            'amenities'          => Amenity::orderBy('name')->get(),
            'selectedAmenityIds' => $hotel->amenities->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $hotel = Hotel::withTrashed()->findOrFail($id);

        $data = $this->validateHotel($request);

        $this->hotelService->update($hotel, $data);

        return redirect()
            ->route('admin.hotels.index')
            ->with('success', "Đã cập nhật khách sạn \"{$hotel->name}\".");
    }

    public function destroy(int $id): RedirectResponse
    {
        $hotel = Hotel::findOrFail($id);

        $this->hotelService->softDelete($hotel);

        return redirect()
            ->route('admin.hotels.index')
            ->with('success', "Đã xóa mềm khách sạn \"{$hotel->name}\".");
    }

    public function restore(int $id): RedirectResponse
    {
        $hotel = Hotel::onlyTrashed()->findOrFail($id);

        $this->hotelService->restore($hotel);

        return redirect()
            ->route('admin.hotels.index')
            ->with('success', "Đã khôi phục khách sạn \"{$hotel->name}\".");
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $hotel = Hotel::findOrFail($id);

        $hotel = $this->hotelService->toggleStatus($hotel);

        return redirect()
            ->route('admin.hotels.index')
            ->with('success', "Đã chuyển khách sạn \"{$hotel->name}\" sang trạng thái \"{$hotel->status}\".");
    }

    private function validateHotel(Request $request): array
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'city'          => ['required', 'string', 'max:100'],
            'district'      => ['nullable', 'string', 'max:100'],
            'address'       => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:5000'],
            'star_rating'   => ['nullable', 'integer', 'between:1,5'],
            'amenity_ids'   => ['nullable', 'array'],
            'amenity_ids.*' => ['integer', 'exists:amenities,id'],
            'images_text'   => ['nullable', 'string'],
        ], [], [
            'name'        => 'tên khách sạn',
            'city'        => 'thành phố',
            'district'    => 'quận/huyện',
            'address'     => 'địa chỉ',
            'description' => 'mô tả',
            'star_rating' => 'xếp hạng sao',
            'amenity_ids' => 'tiện ích',
        ]);

        $data['images'] = collect(explode("\n", $data['images_text'] ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        // Checkbox bỏ chọn hết sẽ không gửi lên request — ép về mảng rỗng
        // để HotelService::update() vẫn đồng bộ (gỡ hết tiện ích) thay vì bỏ qua.
        $data['amenity_ids'] = $data['amenity_ids'] ?? [];

        unset($data['images_text']);

        return $data;
    }
}
