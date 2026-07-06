<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

class ServiceService
{
    public function list(): Collection
    {
        return Service::withTrashed()->latest()->get();
    }

    /**
     * Dịch vụ active dùng cho form đặt phòng (khách chọn).
     */
    public function activePublic(): Collection
    {
        return Service::active()->orderBy('name')->get();
    }

    public function find(int $id): Service
    {
        return Service::withTrashed()->findOrFail($id);
    }

    public function create(array $data): Service
    {
        return Service::create($data);
    }

    public function update(Service $service, array $data): Service
    {
        $service->update($data);

        return $service->fresh();
    }

    public function delete(Service $service): void
    {
        $service->delete();
    }

    public function restore(Service $service): void
    {
        $service->restore();
    }
}
