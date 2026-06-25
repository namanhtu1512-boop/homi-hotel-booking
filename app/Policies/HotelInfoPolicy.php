<?php

namespace App\Policies;

use App\Models\HotelInfo;
use App\Models\User;

/**
 * HotelInfoPolicy — kiểm soát quyền thao tác với thông tin khách sạn singleton.
 *
 * Quy tắc:
 *  - admin/staff: xem, sửa, đổi trạng thái bảo trì, quản lý ảnh.
 *  - customer / unauthenticated: không có quyền gì (chỉ xem qua API public).
 *
 * Không có create/delete/restore/forceDelete vì hotel_info chỉ có đúng 1
 * bản ghi singleton — không bao giờ được tạo mới hay xóa.
 *
 * Policy được Laravel tự động discover theo quy ước:
 *   Model HotelInfo → app/Policies/HotelInfoPolicy.php
 */
class HotelInfoPolicy
{
    /**
     * Xem thông tin khách sạn ở khu vực quản trị (GET /admin/hotel-info).
     */
    public function view(User $user, HotelInfo $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Sửa thông tin khách sạn (PUT /admin/hotel-info).
     */
    public function update(User $user, HotelInfo $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Bật/tắt trạng thái bảo trì (PATCH /admin/hotel-info/toggle-maintenance).
     */
    public function toggleStatus(User $user, HotelInfo $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Xóa ảnh của khách sạn (DELETE /admin/hotel-info/images/{imageId}).
     */
    public function manageImages(User $user, HotelInfo $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }
}
