<?php

namespace App\Policies;

use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;

/**
 * RoomTypePolicy — kiểm soát quyền thao tác với loại phòng theo role.
 *
 * Quy ước giống HotelPolicy (xem [[HotelPolicy]]):
 *  - admin: toàn quyền, kể cả forceDelete.
 *  - staff: xem, thêm, sửa, xóa mềm, khôi phục, đổi giá/số lượng.
 *  - customer / unauthenticated: không có quyền gì.
 *
 * Quan hệ hotel-room_type: một room_type luôn thuộc 1 hotel (belongsTo).
 * Quyền thao tác room_type không phụ thuộc trạng thái hotel (admin/staff
 * vẫn cần sửa được dữ liệu phòng của khách sạn đang ẩn) — rule "không cho
 * tạo phòng mới khi hotel đã ẩn" được xử lý ở RoomTypeService::assertHotelActive(),
 * không phải ở policy, vì đây là rule nghiệp vụ chứ không phải rule phân quyền.
 */
class RoomTypePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    public function view(User $user, RoomType $roomType): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Tạo loại phòng mới cho một khách sạn cụ thể.
     * Nhận thêm Hotel để dành chỗ cho rule phân quyền theo hotel sau này nếu cần.
     */
    public function create(User $user, Hotel $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    public function update(User $user, RoomType $roomType): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    public function delete(User $user, RoomType $roomType): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    public function restore(User $user, RoomType $roomType): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Xóa cứng vĩnh viễn — chỉ admin.
     */
    public function forceDelete(User $user, RoomType $roomType): bool
    {
        return $user->role === 'admin';
    }

    public function updatePrice(User $user, RoomType $roomType): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    public function updateInventory(User $user, RoomType $roomType): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    public function manageImages(User $user, RoomType $roomType): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }
}
