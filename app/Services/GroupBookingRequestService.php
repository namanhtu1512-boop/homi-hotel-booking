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
        $request->update(['status' => 'contacted']);

        return $request->fresh();
    }

    public function delete(GroupBookingRequest $request): void
    {
        $request->delete();
    }
}
