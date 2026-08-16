<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * AuditLogService — ghi nhận hành động quản trị nhạy cảm (ai làm gì, lúc nào,
 * trên đối tượng nào). Dùng cho các thao tác admin/staff thay đổi dữ liệu
 * quan trọng: khóa tài khoản, CRUD khách sạn, CRUD loại phòng...
 *
 * Không log các thao tác đọc (GET) — chỉ log thao tác ghi có ảnh hưởng dữ liệu.
 */
class AuditLogService
{
    public function log(string $action, ?Model $subject = null, ?string $description = null): AuditLog
    {
        return AuditLog::create([
            'user_id'        => Auth::id(),
            'action'         => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id'   => $subject?->getKey(),
            'description'    => $description,
            'ip_address'     => Request::ip(),
        ]);
    }

    /**
     * Lịch sử thay đổi của một đối tượng cụ thể (vd: trang chi tiết phòng).
     */
    public function forSubject(Model $subject)
    {
        return AuditLog::with('user')
            ->where('auditable_type', $subject->getMorphClass())
            ->where('auditable_id', $subject->getKey())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Trang "Nhật ký hệ thống" (chỉ admin) — liệt kê toàn bộ AuditLog,
     * không riêng booking, lọc theo nhân viên/loại hành động/khoảng ngày.
     */
    public function listForAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        return $query->paginate($perPage)->appends($filters);
    }
}
