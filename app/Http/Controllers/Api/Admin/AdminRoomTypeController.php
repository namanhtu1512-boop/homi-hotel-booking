<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\CreateRoomTypeRequest;
use App\Http\Requests\RoomType\UpdateRoomTypeInventoryRequest;
use App\Http\Requests\RoomType\UpdateRoomTypePriceRequest;
use App\Http\Requests\RoomType\UpdateRoomTypeRequest;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Services\ImageService;
use App\Services\RoomTypeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdminRoomTypeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly RoomTypeService $roomTypeService,
        private readonly ImageService $imageService,
    ) {}

    public function index(int $hotelId): JsonResponse
    {
        Hotel::findOrFail($hotelId);
        $roomTypes = $this->roomTypeService->listByHotel($hotelId, adminView: true);

        return $this->success($roomTypes);
    }

    public function store(CreateRoomTypeRequest $request, int $hotelId): JsonResponse
    {
        $hotel    = Hotel::findOrFail($hotelId);
        $roomType = $this->roomTypeService->create($hotel, $request->validated());

        return $this->created($roomType, 'Tạo loại phòng thành công.');
    }

    public function show(int $id): JsonResponse
    {
        $roomType = RoomType::withTrashed()->with(['images', 'hotel'])->findOrFail($id);

        return $this->success($roomType);
    }

    public function update(UpdateRoomTypeRequest $request, int $id): JsonResponse
    {
        $roomType = RoomType::findOrFail($id);
        $roomType = $this->roomTypeService->update($roomType, $request->validated());

        return $this->success($roomType, 'Cập nhật loại phòng thành công.');
    }

    public function destroy(int $id): JsonResponse
    {
        $roomType = RoomType::findOrFail($id);
        $this->roomTypeService->softDeleteOrDeactivate($roomType);

        return $this->success(null, 'Xóa loại phòng thành công.');
    }

    public function restore(int $id): JsonResponse
    {
        $roomType = RoomType::onlyTrashed()->findOrFail($id);
        $this->roomTypeService->restore($roomType);

        return $this->success(null, 'Khôi phục loại phòng thành công.');
    }

    public function updatePrice(UpdateRoomTypePriceRequest $request, int $id): JsonResponse
    {
        $roomType = RoomType::findOrFail($id);
        $roomType = $this->roomTypeService->updatePrice(
            $roomType,
            (float) $request->validated()['price_per_night'],
        );

        return $this->success($roomType, 'Cập nhật giá thành công.');
    }

    public function updateInventory(UpdateRoomTypeInventoryRequest $request, int $id): JsonResponse
    {
        $roomType = RoomType::findOrFail($id);
        $roomType = $this->roomTypeService->updateInventory(
            $roomType,
            (int) $request->validated()['total_rooms'],
        );

        return $this->success($roomType, 'Cập nhật số lượng phòng thành công.');
    }

    public function destroyImage(int $roomTypeId, int $imageId): JsonResponse
    {
        $roomType = RoomType::findOrFail($roomTypeId);
        $deleted  = $this->imageService->deleteRoomTypeImage($roomType, $imageId);

        if (!$deleted) {
            return $this->error('Không tìm thấy ảnh.', 404);
        }

        return $this->success(null, 'Xóa ảnh thành công.');
    }
}
