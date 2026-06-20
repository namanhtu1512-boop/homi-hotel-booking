<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Services\AuditLogService;
use App\Services\HotelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HotelInfoController extends Controller
{
    public function __construct(
        private readonly HotelService $hotelService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function edit(): View
    {
        $hotel = $this->hotelService->singleton();

        return view('admin.hotel-info.edit', [
            'hotel'              => $hotel,
            'amenities'          => Amenity::orderBy('name')->get(),
            'selectedAmenityIds' => $hotel->amenities->pluck('id')->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $this->validateHotel($request);

        $hotel = $this->hotelService->update($data);
        $this->auditLogService->log('hotel_info.updated', $hotel);

        return redirect()
            ->route('admin.hotel-info.edit')
            ->with('success', 'Đã cập nhật thông tin khách sạn.');
    }

    private function validateHotel(Request $request): array
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'address'         => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'hotline'         => ['nullable', 'string', 'max:20'],
            'email'           => ['nullable', 'email', 'max:255'],
            'check_in_time'   => ['nullable', 'string', 'max:20'],
            'check_out_time'  => ['nullable', 'string', 'max:20'],
            'policies'        => ['nullable', 'string', 'max:5000'],
            'star_rating'     => ['nullable', 'integer', 'between:1,5'],
            'is_open'         => ['nullable', 'boolean'],
            'amenity_ids'     => ['nullable', 'array'],
            'amenity_ids.*'   => ['integer', 'exists:amenities,id'],
            'images_text'     => ['nullable', 'string'],
        ], [], [
            'name'           => 'tên khách sạn',
            'address'        => 'địa chỉ',
            'description'    => 'mô tả',
            'hotline'        => 'hotline',
            'email'          => 'email',
            'check_in_time'  => 'giờ nhận phòng',
            'check_out_time' => 'giờ trả phòng',
            'policies'       => 'chính sách',
            'star_rating'    => 'xếp hạng sao',
            'amenity_ids'    => 'tiện ích',
        ]);

        $data['images'] = collect(explode("\n", $data['images_text'] ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $data['amenity_ids'] = $data['amenity_ids'] ?? [];
        $data['is_open'] = $request->boolean('is_open');

        unset($data['images_text']);

        return $data;
    }
}
