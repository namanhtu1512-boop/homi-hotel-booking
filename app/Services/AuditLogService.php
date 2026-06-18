<?php

namespace App\Services;

use App\Models\AuditLog;
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
}
