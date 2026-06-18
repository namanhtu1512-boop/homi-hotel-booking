<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * Danh sách người dùng (có lọc role, tìm kiếm tên/email, phân trang).
     * GET /api/v1/admin/users?role=customer&search=abc&per_page=15
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'role'     => ['nullable', Rule::in(['customer', 'staff', 'admin'])],
            'search'   => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = User::query()->orderBy('created_at', 'desc');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        $paginator = $query->paginate($request->integer('per_page', 15));

        return $this->paginated($paginator, 'users');
    }

    /**
     * Chi tiết một người dùng.
     * GET /api/v1/admin/users/{user}
     */
    public function show(User $user): JsonResponse
    {
        return $this->success(['user' => $user]);
    }

    /**
     * Khóa / mở khóa tài khoản người dùng.
     * PATCH /api/v1/admin/users/{user}/toggle-status
     *
     * Không cho phép tự khóa bản thân.
     * Chỉ admin mới được khóa tài khoản staff hoặc admin khác.
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return $this->error('Không thể khóa tài khoản của chính mình.', 422);
        }

        $newStatus = $user->status === 'active' ? 'locked' : 'active';
        $user->update(['status' => $newStatus]);

        $message = $newStatus === 'locked'
            ? "Đã khóa tài khoản {$user->name}."
            : "Đã mở khóa tài khoản {$user->name}.";

        $this->auditLog->log('user.status_toggled', $user, $message);

        return $this->success(['user' => $user->fresh()], $message);
    }
}
