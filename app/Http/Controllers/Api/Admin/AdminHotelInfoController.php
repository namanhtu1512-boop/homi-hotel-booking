<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hotel\UpdateHotelInfoRequest;
use App\Models\HotelInfo;
use App\Services\AuditLogService;
use App\Services\HotelInfoService;
use App\Services\ImageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Quản trị thông tin khách sạn singleton — chỉ xem/sửa/đổi trạng thái bảo
 * trì/quản lý ảnh. Không có index/store/destroy/restore vì hotel_info
 * luôn chỉ có đúng 1 bản ghi.
 */
class AdminHotelInfoController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly HotelInfoService $hotelInfoService,
        private readonly ImageService $imageService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function show(): JsonResponse
    {
        $hotel = HotelInfo::instance();

        $this->authorize('view', $hotel);

        return $this->success($this->hotelInfoService->get());
    }

    public function update(UpdateHotelInfoRequest $request): JsonResponse
    {
        $hotel = HotelInfo::instance();

        $this->authorize('update', $hotel);

        $hotel = $this->hotelInfoService->update($request->validated());

        $this->auditLog->log('hotel_info.updated', $hotel, "Cập nhật thông tin khách sạn \"{$hotel->name}\".");

        return $this->success($hotel, 'Cập nhật thông tin khách sạn thành công.');
    }

    public function toggleMaintenance(): JsonResponse
    {
        $hotel = HotelInfo::instance();

        $this->authorize('toggleStatus', $hotel);

        $hotel = $this->hotelInfoService->toggleMaintenance();

        $this->auditLog->log('hotel_info.status_toggled', $hotel, "Đổi trạng thái khách sạn thành \"{$hotel->status}\".");

        return $this->success($hotel, 'Cập nhật trạng thái khách sạn thành công.');
    }

    public function destroyImage(int $imageId): JsonResponse
    {
        $hotel = HotelInfo::instance();

        $this->authorize('manageImages', $hotel);

        $deleted = $this->imageService->deleteHotelInfoImage($hotel, $imageId);

        if (! $deleted) {
            return $this->error('Không tìm thấy ảnh.', 404);
        }

        return $this->success(null, 'Xóa ảnh thành công.');
    }
}
