<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\FilterRoomTypeRequest;
use App\Services\RoomTypeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * BE2 Tuần 7 — Danh sách và chi tiết loại phòng cho khách (không cần đăng nhập).
 * Chỉ trả về phòng active; không có create/update/delete ở đây.
 */
class PublicRoomTypeController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly RoomTypeService $roomTypeService) {}

    /**
     * GET /api/v1/room-types
     * Danh sách phòng active có filter: keyword, min_price, max_price, capacity.
     * check_in/check_out được validate nhưng chưa lọc availability (Tuần 9).
     */
    public function index(FilterRoomTypeRequest $request): JsonResponse
    {
        $perPage   = (int) ($request->validated()['per_page'] ?? 12);
        $roomTypes = $this->roomTypeService->search($request->filters(), $perPage);

        return $this->success($roomTypes);
    }

    /**
     * GET /api/v1/room-types/{id}
     * Chi tiết một loại phòng active.
     */
    public function show(int $id): JsonResponse
    {
        $roomType = $this->roomTypeService->findActive($id);

        return $this->success($roomType->load('images'));
    }
}
