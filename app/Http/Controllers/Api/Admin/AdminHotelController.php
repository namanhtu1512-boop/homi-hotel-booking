<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hotel\CreateHotelRequest;
use App\Http\Requests\Hotel\UpdateHotelRequest;
use App\Models\Hotel;
use App\Services\HotelService;
use App\Services\ImageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminHotelController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly HotelService $hotelService,
        private readonly ImageService $imageService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $hotels = $this->hotelService->adminList(
            filters: $request->only(['search', 'status']),
            perPage: (int) $request->input('per_page', 15),
        );

        return $this->paginated($hotels, 'hotels');
    }

    public function store(CreateHotelRequest $request): JsonResponse
    {
        $hotel = $this->hotelService->create($request->validated());

        return $this->created($hotel, 'Tạo khách sạn thành công.');
    }

    public function show(int $id): JsonResponse
    {
        $hotel = $this->hotelService->adminFind($id);

        return $this->success($hotel);
    }

    public function update(UpdateHotelRequest $request, int $id): JsonResponse
    {
        $hotel = Hotel::withTrashed()->findOrFail($id);
        $hotel = $this->hotelService->update($hotel, $request->validated());

        return $this->success($hotel, 'Cập nhật khách sạn thành công.');
    }

    public function destroy(int $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);
        $this->hotelService->softDelete($hotel);

        return $this->success(null, 'Xóa khách sạn thành công.');
    }

    public function restore(int $id): JsonResponse
    {
        $hotel = Hotel::onlyTrashed()->findOrFail($id);
        $this->hotelService->restore($hotel);

        return $this->success(null, 'Khôi phục khách sạn thành công.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);
        $hotel = $this->hotelService->toggleStatus($hotel);

        return $this->success($hotel, 'Cập nhật trạng thái khách sạn thành công.');
    }

    public function destroyImage(int $hotelId, int $imageId): JsonResponse
    {
        $hotel   = Hotel::findOrFail($hotelId);
        $deleted = $this->imageService->deleteHotelImage($hotel, $imageId);

        if (!$deleted) {
            return $this->error('Không tìm thấy ảnh.', 404);
        }

        return $this->success(null, 'Xóa ảnh thành công.');
    }
}
