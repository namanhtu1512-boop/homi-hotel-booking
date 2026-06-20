<?php

namespace App\Policies;

use App\Models\HotelInfo;
use App\Models\User;

/**
 * HotelPolicy — kiểm soát quyền xem/sửa thông tin khách sạn (singleton).
 *
 * Quy tắc: admin và staff đều xem/sửa được; customer/unauthenticated không có quyền.
 * Không còn create/delete/restore/forceDelete/toggleStatus vì chỉ có 1 bản ghi duy nhất
 * (is_open được sửa trực tiếp qua form update, không phải action riêng).
 */
class HotelPolicy
{
    public function view(User $user, HotelInfo $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    public function update(User $user, HotelInfo $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    public function manageImages(User $user, HotelInfo $hotel): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }
}
