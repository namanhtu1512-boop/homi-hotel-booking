<?php

namespace App\Policies;

use App\Models\RoomType;
use App\Models\User;

/**
 * RoomTypePolicy — kiểm soát quyền thao tác với loại phòng theo role.
 *
 * Quy ước giống HotelInfoPolicy (xem [[HotelInfoPolicy]]):
 *  - admin: toàn quyền, kể cả forceDelete.
 *  - staff: xem, thêm, sửa, xóa mềm, khôi phục, đổi giá/số lượng.
 *  - customer / unauthenticated: không có quyền gì.
 *
 * Vì hệ thống chỉ có 1 khách sạn, room_types không còn gắn với hotel_id —
 * rule "không cho tạo phòng mới khi khách sạn đang bảo trì" được xử lý ở
 * RoomTypeService::assertHotelOperational(), không phải ở policy, vì đây
 * là rule nghiệp vụ chứ không phải rule phân quyền.
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

    public function create(User $user): bool
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
