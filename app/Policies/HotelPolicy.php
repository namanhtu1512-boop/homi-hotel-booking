<?php

namespace App\Policies;

use App\Models\Hotel;
use App\Models\User;

/**
 * HotelPolicy — kiểm soát quyền thao tác với khách sạn theo role.
 *
 * Quy tắc:
 *  - admin: toàn quyền, kể cả forceDelete.
 *  - staff: xem, thêm, sửa, xóa mềm, khôi phục, toggle status.
 *  - customer / unauthenticated: không có quyền gì.
 *
 * Policy được Laravel tự động discover theo quy ước:
 *   Model Hotel → app/Policies/HotelPolicy.php
 */
class HotelPolicy
{
    /**
     * Danh sách khách sạn (GET /admin/hotels).
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Xem chi tiết một khách sạn (GET /admin/hotels/{id}).
     */
    public function view(User $user, Hotel $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Tạo khách sạn mới (POST /admin/hotels).
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Sửa thông tin khách sạn (PUT /admin/hotels/{id}).
     */
    public function update(User $user, Hotel $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Xóa mềm khách sạn (DELETE /admin/hotels/{id}).
     */
    public function delete(User $user, Hotel $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Khôi phục khách sạn đã xóa mềm (POST /admin/hotels/{id}/restore).
     */
    public function restore(User $user, Hotel $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Xóa cứng vĩnh viễn — chỉ admin.
     */
    public function forceDelete(User $user, Hotel $hotel): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Ẩn / hiện khách sạn (PATCH /admin/hotels/{id}/toggle-status).
     */
    public function toggleStatus(User $user, Hotel $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Xóa ảnh của khách sạn (DELETE /admin/hotels/{hotelId}/images/{imageId}).
     */
    public function manageImages(User $user, Hotel $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }
}
