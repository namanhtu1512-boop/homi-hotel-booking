<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminAuditLogController extends Controller
{
    use ApiResponse;

    /**
     * Danh sách audit log (lọc theo action, user, đối tượng), mới nhất trước.
     * GET /api/v1/admin/audit-logs?action=hotel.created&user_id=3&per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'action'   => ['nullable', 'string', 'max:100'],
            'user_id'  => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = AuditLog::query()->with('user:id,name,email,role')->orderBy('created_at', 'desc');

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        $paginator = $query->paginate($request->integer('per_page', 20));

        return $this->paginated($paginator, 'logs');
    }
}
