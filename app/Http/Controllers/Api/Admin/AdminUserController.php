<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
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

    public function toggleStatus(User $user): JsonResponse
    {
        $user->update([
            'status' => $user->status === 'active' ? 'locked' : 'active',
        ]);

        return $this->success($user->fresh(), 'Cập nhật trạng thái thành công.');
    }
}
