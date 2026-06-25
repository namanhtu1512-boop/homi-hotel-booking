<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\CreateRoomTypeRequest;
use App\Http\Requests\RoomType\UpdateRoomTypeInventoryRequest;
use App\Http\Requests\RoomType\UpdateRoomTypePriceRequest;
use App\Http\Requests\RoomType\UpdateRoomTypeRequest;
use App\Models\RoomType;
use App\Services\AuditLogService;
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
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', RoomType::class);

        $roomTypes = $this->roomTypeService->list(adminView: true);

        return $this->success($roomTypes);
    }

    public function store(CreateRoomTypeRequest $request): JsonResponse
    {
        $this->authorize('create', RoomType::class);

        $roomType = $this->roomTypeService->create($request->validated());

        $this->auditLog->log('room_type.created', $roomType, "Tạo loại phòng \"{$roomType->name}\".");

        return $this->created($roomType, 'Tạo loại phòng thành công.');
    }

    public function show(int $id): JsonResponse
    {
        $roomType = RoomType::withTrashed()->with('images')->findOrFail($id);

        $this->authorize('view', $roomType);

        return $this->success($roomType);
    }

    public function update(UpdateRoomTypeRequest $request, int $id): JsonResponse
    {
        $roomType = RoomType::findOrFail($id);

        $this->authorize('update', $roomType);

        $roomType = $this->roomTypeService->update($roomType, $request->validated());

        $this->auditLog->log('room_type.updated', $roomType, "Cập nhật loại phòng \"{$roomType->name}\".");

        return $this->success($roomType, 'Cập nhật loại phòng thành công.');
    }

    public function destroy(int $id): JsonResponse
    {
        $roomType = RoomType::findOrFail($id);

        $this->authorize('delete', $roomType);

        $this->roomTypeService->softDeleteOrDeactivate($roomType);

        $this->auditLog->log('room_type.deleted', $roomType, "Xóa loại phòng \"{$roomType->name}\".");

        return $this->success(null, 'Xóa loại phòng thành công.');
    }

    public function restore(int $id): JsonResponse
    {
        $roomType = RoomType::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $roomType);

        $this->roomTypeService->restore($roomType);

        $this->auditLog->log('room_type.restored', $roomType, "Khôi phục loại phòng \"{$roomType->name}\".");

        return $this->success(null, 'Khôi phục loại phòng thành công.');
    }

    public function updatePrice(UpdateRoomTypePriceRequest $request, int $id): JsonResponse
    {
        $roomType = RoomType::findOrFail($id);

        $this->authorize('updatePrice', $roomType);

        $roomType = $this->roomTypeService->updatePrice(
            $roomType,
            (float) $request->validated()['price_per_night'],
        );

        $this->auditLog->log('room_type.price_updated', $roomType, "Đổi giá loại phòng \"{$roomType->name}\" thành {$roomType->price_per_night}.");

        return $this->success($roomType, 'Cập nhật giá thành công.');
    }

    public function updateInventory(UpdateRoomTypeInventoryRequest $request, int $id): JsonResponse
    {
        $roomType = RoomType::findOrFail($id);

        $this->authorize('updateInventory', $roomType);

        $roomType = $this->roomTypeService->updateInventory(
            $roomType,
            (int) $request->validated()['total_rooms'],
        );

        $this->auditLog->log('room_type.inventory_updated', $roomType, "Đổi số lượng phòng \"{$roomType->name}\" thành {$roomType->total_rooms}.");

        return $this->success($roomType, 'Cập nhật số lượng phòng thành công.');
    }

    public function destroyImage(int $roomTypeId, int $imageId): JsonResponse
    {
        $roomType = RoomType::findOrFail($roomTypeId);

        $this->authorize('manageImages', $roomType);

        $deleted = $this->imageService->deleteRoomTypeImage($roomType, $imageId);

        if (! $deleted) {
            return $this->error('Không tìm thấy ảnh.', 404);
        }

        return $this->success(null, 'Xóa ảnh thành công.');
    }
}
