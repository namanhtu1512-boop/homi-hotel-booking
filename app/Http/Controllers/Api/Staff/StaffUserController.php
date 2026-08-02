<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Xem danh sách người dùng dành cho staff — chỉ xem (index/show), không có
 * toggleStatus (khóa/mở khóa tài khoản chỉ admin mới làm, xem
 * AdminUserController::toggleStatus() và routes/api.php).
 */
class StaffUserController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $users = User::orderBy('created_at', 'desc')->paginate(20);

        return $this->success($users);
    }

    public function show(User $user): JsonResponse
    {
        return $this->success($user);
    }
}
