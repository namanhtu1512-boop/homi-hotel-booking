<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hotel\AdminListHotelRequest;
use App\Http\Requests\Hotel\CreateHotelRequest;
use App\Http\Requests\Hotel\UpdateHotelRequest;
use App\Models\Hotel;
use App\Services\AuditLogService;
use App\Services\HotelService;
use App\Services\ImageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdminHotelController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly HotelService $hotelService,
        private readonly ImageService $imageService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(AdminListHotelRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Hotel::class);

        $hotels = $this->hotelService->adminList(
            filters: $request->only(['search', 'status', 'sort_by', 'sort_order']),
            perPage: $request->perPage(),
        );

        return $this->paginated($hotels, 'hotels', [
            'sort_by'    => $request->sortBy(),
            'sort_order' => $request->sortOrder(),
        ]);
    }

    public function store(CreateHotelRequest $request): JsonResponse
    {
        $this->authorize('create', Hotel::class);

        $hotel = $this->hotelService->create($request->validated());

        $this->auditLog->log('hotel.created', $hotel, "Tạo khách sạn \"{$hotel->name}\".");

        return $this->created($hotel, 'Tạo khách sạn thành công.');
    }

    public function show(int $id): JsonResponse
    {
        $hotel = Hotel::withTrashed()->findOrFail($id);

        $this->authorize('view', $hotel);

        return $this->success($this->hotelService->adminFind($id));
    }

    public function update(UpdateHotelRequest $request, int $id): JsonResponse
    {
        $hotel = Hotel::withTrashed()->findOrFail($id);

        $this->authorize('update', $hotel);

        $hotel = $this->hotelService->update($hotel, $request->validated());

        $this->auditLog->log('hotel.updated', $hotel, "Cập nhật khách sạn \"{$hotel->name}\".");

        return $this->success($hotel, 'Cập nhật khách sạn thành công.');
    }

    public function destroy(int $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);

        $this->authorize('delete', $hotel);

        $this->hotelService->softDelete($hotel);

        $this->auditLog->log('hotel.deleted', $hotel, "Xóa mềm khách sạn \"{$hotel->name}\".");

        return $this->success(null, 'Xóa khách sạn thành công.');
    }

    public function restore(int $id): JsonResponse
    {
        $hotel = Hotel::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $hotel);

        $this->hotelService->restore($hotel);

        $this->auditLog->log('hotel.restored', $hotel, "Khôi phục khách sạn \"{$hotel->name}\".");

        return $this->success(null, 'Khôi phục khách sạn thành công.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);

        $this->authorize('toggleStatus', $hotel);

        $hotel = $this->hotelService->toggleStatus($hotel);

        $this->auditLog->log('hotel.status_toggled', $hotel, "Đổi trạng thái khách sạn \"{$hotel->name}\" thành \"{$hotel->status}\".");

        return $this->success($hotel, 'Cập nhật trạng thái khách sạn thành công.');
    }

    public function destroyImage(int $hotelId, int $imageId): JsonResponse
    {
        $hotel = Hotel::findOrFail($hotelId);

        $this->authorize('manageImages', $hotel);

        $deleted = $this->imageService->deleteHotelImage($hotel, $imageId);

        if (! $deleted) {
            return $this->error('Không tìm thấy ảnh.', 404);
        }

        return $this->success(null, 'Xóa ảnh thành công.');
    }
}
