<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Services\HotelInfoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HotelInfoController extends Controller
{
    public function __construct(private readonly HotelInfoService $hotelInfoService) {}

    public function show(): View
    {
        $hotel = $this->hotelInfoService->get();

        return view('admin.hotel-info.show', ['hotel' => $hotel]);
    }

    public function edit(): View
    {
        $hotel = $this->hotelInfoService->get();

        return view('admin.hotel-info.edit', [
            'hotel'              => $hotel,
            'amenities'          => Amenity::orderBy('name')->get(),
            'selectedAmenityIds' => $hotel->amenities->pluck('id')->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $this->validateHotel($request);

        $hotel = $this->hotelInfoService->update($data);

        return redirect()
            ->route('admin.hotel-info.show')
            ->with('success', "Đã cập nhật thông tin khách sạn \"{$hotel->name}\".");
    }

    public function toggleMaintenance(): RedirectResponse
    {
        $hotel = $this->hotelInfoService->toggleMaintenance();

        return redirect()
            ->route('admin.hotel-info.show')
            ->with('success', "Đã chuyển khách sạn sang trạng thái \"{$hotel->status}\".");
    }

    private function validateHotel(Request $request): array
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'address'        => ['required', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'email'          => ['nullable', 'email', 'max:255'],
            'description'    => ['nullable', 'string', 'max:5000'],
            'check_in_time'  => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'policies'       => ['nullable', 'string', 'max:5000'],
            'star_rating'    => ['nullable', 'integer', 'between:1,5'],
            'amenity_ids'    => ['nullable', 'array'],
            'amenity_ids.*'  => ['integer', 'exists:amenities,id'],
            'images_text'    => ['nullable', 'string'],
            'image_files'    => ['nullable', 'array'],
            'image_files.*'  => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], [
            'name'           => 'tên khách sạn',
            'address'        => 'địa chỉ',
            'phone'          => 'số điện thoại',
            'email'          => 'email',
            'description'    => 'mô tả',
            'check_in_time'  => 'giờ nhận phòng',
            'check_out_time' => 'giờ trả phòng',
            'policies'       => 'chính sách',
            'star_rating'    => 'xếp hạng sao',
            'amenity_ids'    => 'tiện ích',
            'image_files.*'  => 'file ảnh',
        ]);

        $textPaths = collect(explode("\n", $data['images_text'] ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $uploadedPaths = [];
        foreach ($request->file('image_files', []) as $file) {
            $uploadedPaths[] = $file->store('hotel', 'public');
        }

        $data['images'] = array_merge($textPaths, $uploadedPaths);

        // Checkbox bỏ chọn hết sẽ không gửi lên request — ép về mảng rỗng
        // để HotelInfoService::update() vẫn đồng bộ (gỡ hết tiện ích) thay vì bỏ qua.
        $data['amenity_ids'] = $data['amenity_ids'] ?? [];

        unset($data['images_text'], $data['image_files']);

        return $data;
    }
}
