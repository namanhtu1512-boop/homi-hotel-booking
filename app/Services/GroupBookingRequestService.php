<?php

namespace App\Services;

use App\Models\GroupBookingRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GroupBookingRequestService
{
    public function create(array $data): GroupBookingRequest
    {
        return GroupBookingRequest::create($data);
    }

    public function adminList(array $filters = []): LengthAwarePaginator
    {
        $query = GroupBookingRequest::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(15)->withQueryString();
    }

    public function markContacted(GroupBookingRequest $request): GroupBookingRequest
    {
        // Không hạ cấp trạng thái 'converted' về lại 'contacted' — 1 yêu cầu
        // đã chuyển thành đơn đặt phòng thì giữ nguyên trạng thái converted.
        if ($request->status === 'converted') {
            return $request;
        }

        $request->update(['status' => 'contacted']);

        return $request->fresh();
    }

    /**
     * Đánh dấu yêu cầu đã được chuyển thành đơn đặt phòng thật (converted).
     * Trạng thái cuối trong vòng đời yêu cầu — khác 'contacted' để UI biết
     * không cho tạo đơn thêm lần nữa từ cùng 1 yêu cầu (tránh đơn trùng).
     */
    public function markConverted(GroupBookingRequest $request): GroupBookingRequest
    {
        $request->update(['status' => 'converted']);

        return $request->fresh();
    }

    public function delete(GroupBookingRequest $request): void
    {
        $request->delete();
    }
}
