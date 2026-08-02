<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hotel\UpdateHotelInfoRequest;
use App\Models\HotelInfo;
use App\Services\AuditLogService;
use App\Services\HotelInfoService;
use App\Services\ImageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Quản lý thông tin khách sạn singleton dành cho staff — cùng quyền hạn với
 * AdminHotelInfoController (xem HotelInfoPolicy: admin/staff xem, sửa, đổi
 * trạng thái bảo trì, quản lý ảnh ngang nhau), chỉ khác namespace/route
 * prefix để tách luồng API admin/staff.
 */
class StaffHotelInfoController extends Controller
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
